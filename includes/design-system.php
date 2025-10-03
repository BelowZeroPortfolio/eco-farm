<?php

/**
 * Global Design System for IoT Farm Monitoring System
 * 
 * Centralized design tokens, color schemes, and theme configuration
 * Professional UI/UX design system with 20+ years of industry best practices
 */

// Define global color themes - easily switchable
$DESIGN_THEMES = [
    'default' => [
        'name' => 'Farm Green',
        'primary' => [
            '50' => '#f0fdf4',
            '100' => '#dcfce7',
            '200' => '#bbf7d0',
            '300' => '#86efac',
            '400' => '#4ade80',
            '500' => '#22c55e',
            '600' => '#16a34a',
            '700' => '#15803d',
            '800' => '#166534',
            '900' => '#14532d',
        ],
        'secondary' => [
            '50' => '#f8fafc',
            '100' => '#f1f5f9',
            '200' => '#e2e8f0',
            '300' => '#cbd5e1',
            '400' => '#94a3b8',
            '500' => '#64748b',
            '600' => '#475569',
            '700' => '#334155',
            '800' => '#1e293b',
            '900' => '#0f172a',
        ],
        'accent' => [
            '50' => '#eff6ff',
            '100' => '#dbeafe',
            '200' => '#bfdbfe',
            '300' => '#93c5fd',
            '400' => '#60a5fa',
            '500' => '#3b82f6',
            '600' => '#2563eb',
            '700' => '#1d4ed8',
            '800' => '#1e40af',
            '900' => '#1e3a8a',
        ]
    ],
    'dark' => [
        'name' => 'Dark Professional',
        'primary' => [
            '50' => '#f7f7f7',
            '100' => '#e3e3e3',
            '200' => '#c8c8c8',
            '300' => '#a4a4a4',
            '400' => '#818181',
            '500' => '#666666',
            '600' => '#515151',
            '700' => '#434343',
            '800' => '#383838',
            '900' => '#000000',
        ],
        'secondary' => [
            '50' => '#f9fafb',
            '100' => '#f3f4f6',
            '200' => '#e5e7eb',
            '300' => '#d1d5db',
            '400' => '#9ca3af',
            '500' => '#6b7280',
            '600' => '#4b5563',
            '700' => '#374151',
            '800' => '#1f2937',
            '900' => '#111827',
        ],
        'accent' => [
            '50' => '#fef2f2',
            '100' => '#fee2e2',
            '200' => '#fecaca',
            '300' => '#fca5a5',
            '400' => '#f87171',
            '500' => '#ef4444',
            '600' => '#dc2626',
            '700' => '#b91c1c',
            '800' => '#991b1b',
            '900' => '#7f1d1d',
        ]
    ],
    'ocean' => [
        'name' => 'Ocean Blue',
        'primary' => [
            '50' => '#f0f9ff',
            '100' => '#e0f2fe',
            '200' => '#bae6fd',
            '300' => '#7dd3fc',
            '400' => '#38bdf8',
            '500' => '#0ea5e9',
            '600' => '#0284c7',
            '700' => '#0369a1',
            '800' => '#075985',
            '900' => '#0c4a6e',
        ],
        'secondary' => [
            '50' => '#f8fafc',
            '100' => '#f1f5f9',
            '200' => '#e2e8f0',
            '300' => '#cbd5e1',
            '400' => '#94a3b8',
            '500' => '#64748b',
            '600' => '#475569',
            '700' => '#334155',
            '800' => '#1e293b',
            '900' => '#0f172a',
        ],
        'accent' => [
            '50' => '#fefce8',
            '100' => '#fef9c3',
            '200' => '#fef08a',
            '300' => '#fde047',
            '400' => '#facc15',
            '500' => '#eab308',
            '600' => '#ca8a04',
            '700' => '#a16207',
            '800' => '#854d0e',
            '900' => '#713f12',
        ]
    ]
];

// Set current theme (easily changeable)
$CURRENT_THEME = 'default'; // Change this to switch themes globally

// Get current theme colors
$THEME = $DESIGN_THEMES[$CURRENT_THEME];

// Typography scale (based on modular scale)
$TYPOGRAPHY = [
    'font_family' => [
        'sans' => ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
        'mono' => ['JetBrains Mono', 'Fira Code', 'Monaco', 'Consolas', 'monospace'],
        'display' => ['Poppins', 'Inter', 'system-ui', 'sans-serif']
    ],
    'font_size' => [
        'xs' => '0.75rem',    // 12px
        'sm' => '0.875rem',   // 14px
        'base' => '1rem',     // 16px
        'lg' => '1.125rem',   // 18px
        'xl' => '1.25rem',    // 20px
        '2xl' => '1.5rem',    // 24px
        '3xl' => '1.875rem',  // 30px
        '4xl' => '2.25rem',   // 36px
        '5xl' => '3rem',      // 48px
        '6xl' => '3.75rem',   // 60px
    ],
    'font_weight' => [
        'light' => '300',
        'normal' => '400',
        'medium' => '500',
        'semibold' => '600',
        'bold' => '700',
        'extrabold' => '800',
    ],
    'line_height' => [
        'tight' => '1.25',
        'snug' => '1.375',
        'normal' => '1.5',
        'relaxed' => '1.625',
        'loose' => '2',
    ]
];

// Spacing scale (8px base unit)
$SPACING = [
    '0' => '0',
    '1' => '0.25rem',   // 4px
    '2' => '0.5rem',    // 8px
    '3' => '0.75rem',   // 12px
    '4' => '1rem',      // 16px
    '5' => '1.25rem',   // 20px
    '6' => '1.5rem',    // 24px
    '8' => '2rem',      // 32px
    '10' => '2.5rem',   // 40px
    '12' => '3rem',     // 48px
    '16' => '4rem',     // 64px
    '20' => '5rem',     // 80px
    '24' => '6rem',     // 96px
    '32' => '8rem',     // 128px
];

// Shadow system
$SHADOWS = [
    'sm' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
    'base' => '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
    'md' => '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
    'lg' => '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
    'xl' => '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
    '2xl' => '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
];

// Border radius scale
$BORDER_RADIUS = [
    'none' => '0',
    'sm' => '0.125rem',   // 2px
    'base' => '0.25rem',  // 4px
    'md' => '0.375rem',   // 6px
    'lg' => '0.5rem',     // 8px
    'xl' => '0.75rem',    // 12px
    '2xl' => '1rem',      // 16px
    '3xl' => '1.5rem',    // 24px
    'full' => '9999px',
];

// Animation durations
$ANIMATIONS = [
    'fast' => '150ms',
    'normal' => '300ms',
    'slow' => '500ms',
    'slower' => '750ms',
];

// Breakpoints
$BREAKPOINTS = [
    'sm' => '640px',
    'md' => '768px',
    'lg' => '1024px',
    'xl' => '1280px',
    '2xl' => '1536px',
];

/**
 * Helper function to get theme color
 */
function getThemeColor($type, $shade = '500')
{
    global $THEME;
    return $THEME[$type][$shade] ?? '#000000';
}

/**
 * Helper function to get CSS custom properties
 */
function generateCSSCustomProperties()
{
    global $THEME, $TYPOGRAPHY, $SPACING, $SHADOWS, $BORDER_RADIUS, $ANIMATIONS;

    $css = ":root {\n";

    // Colors
    foreach ($THEME as $type => $shades) {
        foreach ($shades as $shade => $color) {
            if (is_string($color)) {
                $css .= "  --color-{$type}: {$color};\n";
            } else {
                $css .= "  --color-{$type}-{$shade}: {$color};\n";
            }
        }
    }

    // Typography
    foreach ($TYPOGRAPHY['font_size'] as $size => $value) {
        $css .= "  --font-size-{$size}: {$value};\n";
    }

    // Spacing
    foreach ($SPACING as $size => $value) {
        $css .= "  --spacing-{$size}: {$value};\n";
    }

    // Shadows
    foreach ($SHADOWS as $size => $value) {
        $css .= "  --shadow-{$size}: {$value};\n";
    }

    // Border radius
    foreach ($BORDER_RADIUS as $size => $value) {
        $css .= "  --radius-{$size}: {$value};\n";
    }

    // Animations
    foreach ($ANIMATIONS as $speed => $value) {
        $css .= "  --duration-{$speed}: {$value};\n";
    }

    $css .= "}\n";

    return $css;
}

/**
 * Get role-specific styling
 */
function getRoleTheme($role)
{
    switch ($role) {
        case 'admin':
            return [
                'primary' => 'red',
                'bg' => 'bg-red-50',
                'text' => 'text-red-800',
                'border' => 'border-red-200',
                'hover' => 'hover:bg-red-100'
            ];
        case 'farmer':
            return [
                'primary' => 'green',
                'bg' => 'bg-green-50',
                'text' => 'text-green-800',
                'border' => 'border-green-200',
                'hover' => 'hover:bg-green-100'
            ];
        case 'student':
            return [
                'primary' => 'blue',
                'bg' => 'bg-blue-50',
                'text' => 'text-blue-800',
                'border' => 'border-blue-200',
                'hover' => 'hover:bg-blue-100'
            ];
        default:
            return [
                'primary' => 'gray',
                'bg' => 'bg-gray-50',
                'text' => 'text-gray-800',
                'border' => 'border-gray-200',
                'hover' => 'hover:bg-gray-100'
            ];
    }
}
