/**
 * Global Language System JavaScript
 * 
 * Provides client-side translation functionality across all pages
 */

// Global translations object (will be populated by PHP)
let globalTranslations = {};
let currentLanguage = 'en';

// Initialize language system
function initializeLanguageSystem(translations, language) {
    globalTranslations = translations;
    currentLanguage = language;
    
    // Apply translations to current page
    updatePageLanguage(currentLanguage);
    
    // Set up language selector if it exists
    setupLanguageSelector();
}

// Update all translatable elements on the page
function updatePageLanguage(lang) {
    const elements = document.querySelectorAll('[data-translate]');
    elements.forEach(element => {
        const key = element.getAttribute('data-translate');
        if (globalTranslations[key]) {
            // Handle different element types
            if (element.tagName === 'INPUT' && (element.type === 'submit' || element.type === 'button')) {
                element.value = globalTranslations[key];
            } else if (element.tagName === 'INPUT' && element.placeholder !== undefined) {
                element.placeholder = globalTranslations[key];
            } else {
                element.textContent = globalTranslations[key];
            }
        }
    });
    
    // Update page title if it has translation
    const titleElement = document.querySelector('title[data-translate]');
    if (titleElement) {
        const key = titleElement.getAttribute('data-translate');
        if (globalTranslations[key]) {
            titleElement.textContent = globalTranslations[key];
        }
    }
    
    // Update any elements with data-translate-attr for attributes
    const attrElements = document.querySelectorAll('[data-translate-attr]');
    attrElements.forEach(element => {
        const attrData = element.getAttribute('data-translate-attr');
        try {
            const attrConfig = JSON.parse(attrData);
            Object.keys(attrConfig).forEach(attr => {
                const key = attrConfig[attr];
                if (globalTranslations[key]) {
                    element.setAttribute(attr, globalTranslations[key]);
                }
            });
        } catch (e) {
            console.warn('Invalid data-translate-attr format:', attrData);
        }
    });
}

// Set up language selector functionality
function setupLanguageSelector() {
    const languageSelect = document.getElementById('language-select');
    if (languageSelect) {
        languageSelect.addEventListener('change', function() {
            const newLanguage = this.value;
            changeLanguage(newLanguage);
        });
    }
    
    // Set up any other language switchers
    const languageSwitchers = document.querySelectorAll('[data-language-switch]');
    languageSwitchers.forEach(switcher => {
        switcher.addEventListener('click', function(e) {
            e.preventDefault();
            const newLanguage = this.getAttribute('data-language-switch');
            changeLanguage(newLanguage);
        });
    });
}

// Change language and update interface
function changeLanguage(newLanguage) {
    if (newLanguage === currentLanguage) return;
    
    // Update current language
    currentLanguage = newLanguage;
    
    // Send AJAX request to update server-side language preference
    updateServerLanguage(newLanguage);
    
    // Fetch new translations and update interface
    fetchTranslations(newLanguage).then(translations => {
        globalTranslations = translations;
        updatePageLanguage(newLanguage);
        
        // Trigger custom event for other scripts to listen to
        document.dispatchEvent(new CustomEvent('languageChanged', {
            detail: { language: newLanguage, translations: translations }
        }));
    });
}

// Update server-side language preference
function updateServerLanguage(language) {
    const formData = new FormData();
    formData.append('action', 'update_language');
    formData.append('language', language);
    
    fetch('includes/language-handler.php', {
        method: 'POST',
        body: formData
    }).catch(error => {
        console.warn('Failed to update server language preference:', error);
    });
}

// Fetch translations for a specific language
async function fetchTranslations(language) {
    try {
        const response = await fetch(`includes/language-handler.php?lang=${language}`);
        const data = await response.json();
        return data.translations || {};
    } catch (error) {
        console.warn('Failed to fetch translations:', error);
        return globalTranslations; // Return current translations as fallback
    }
}

// Helper function to translate text programmatically
function translate(key, fallback = null) {
    return globalTranslations[key] || fallback || key;
}

// Helper function to translate and update element
function translateElement(element, key) {
    const translation = translate(key);
    if (element.tagName === 'INPUT' && (element.type === 'submit' || element.type === 'button')) {
        element.value = translation;
    } else if (element.tagName === 'INPUT' && element.placeholder !== undefined) {
        element.placeholder = translation;
    } else {
        element.textContent = translation;
    }
}

// Add translation attributes to elements dynamically
function addTranslationAttribute(element, key) {
    element.setAttribute('data-translate', key);
    translateElement(element, key);
}

// Language-aware date formatting
function formatDate(date, options = {}) {
    const locale = currentLanguage === 'tl' ? 'tl-PH' : 'en-US';
    return new Intl.DateTimeFormat(locale, options).format(date);
}

// Language-aware number formatting
function formatNumber(number, options = {}) {
    const locale = currentLanguage === 'tl' ? 'tl-PH' : 'en-US';
    return new Intl.NumberFormat(locale, options).format(number);
}

// Export functions for use in other scripts
window.LanguageSystem = {
    initialize: initializeLanguageSystem,
    changeLanguage: changeLanguage,
    translate: translate,
    translateElement: translateElement,
    addTranslationAttribute: addTranslationAttribute,
    formatDate: formatDate,
    formatNumber: formatNumber,
    getCurrentLanguage: () => currentLanguage,
    getTranslations: () => globalTranslations
};

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if translations are provided by the page
    if (typeof pageTranslations !== 'undefined' && typeof pageLanguage !== 'undefined') {
        initializeLanguageSystem(pageTranslations, pageLanguage);
    }
});