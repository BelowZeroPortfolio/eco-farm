<?php

/**
 * Pest Severity Configuration
 * Maps pest types to severity levels and suggested actions
 */

class PestSeverityConfig
{
    /**
     * Get severity level and suggested actions for a specific pest type
     * 
     * @param string $pestType The detected pest type
     * @return array ['severity' => string, 'actions' => string]
     */
    public static function getPestInfo($pestType)
    {
        // Normalize pest name for matching
        $pestLower = strtolower(trim($pestType));

        // Critical pests - Can cause 50-100% crop loss, immediate action required
        $criticalPests = [
            'brown plant hopper' => 'CRITICAL: Causes hopper burn, 70-100% yield loss possible. Transmits rice grassy stunt virus. IMMEDIATE ACTION: Apply imidacloprid 200g/ha or thiamethoxam 100g/ha. Drain field completely for 3-4 days. Scout every 2 days. Economic threshold: 10 hoppers per plant.',
            'locustoidea' => 'CRITICAL: Swarms can destroy entire fields in hours. 100% crop loss possible. IMMEDIATE ACTION: Apply malathion 1000ml/ha or lambda-cyhalothrin 250ml/ha. Coordinate with neighboring farms. Use smoke and noise barriers. Report to agricultural authorities immediately.',
            'army worm' => 'CRITICAL: Migrates in large groups, consumes entire plants. 80-100% defoliation possible. IMMEDIATE ACTION: Apply chlorpyrifos 500ml/ha or emamectin benzoate 200g/ha at dusk. Scout fields at dawn. Treat field borders first. Economic threshold: 2-3 larvae per plant.',
            'asiatic rice borer' => 'CRITICAL: Causes dead hearts (seedling stage) and white heads (reproductive stage). 30-80% yield loss. IMMEDIATE ACTION: Apply cartap hydrochloride 1kg/ha or chlorantraniliprole 60ml/ha. Cut and burn stubble after harvest. Use pheromone traps. Treat at egg hatching stage.',
            'rice gall midge' => 'CRITICAL: Forms silver shoots, no grain production from affected tillers. 20-70% yield loss. IMMEDIATE ACTION: Apply carbofuran 1kg/ha or fipronil 100g/ha at tillering stage. Drain field for 3 days. Use resistant varieties (e.g., Abhaya, Aganni). Remove wild grasses.',
            'corn borer' => 'CRITICAL: Tunnels weaken stalks causing lodging. 20-50% yield loss. IMMEDIATE ACTION: Apply Bt (Bacillus thuringiensis) 1kg/ha or spinosad 200ml/ha when 10% plants show feeding damage. Apply granules in whorl. Remove and destroy infested plants. Plant early to avoid peak populations.',
            'bactrocera minax' => 'CRITICAL: Chinese citrus fly causes 40-90% fruit drop and unmarketable fruit. IMMEDIATE ACTION: Install protein bait traps (1 per 2 trees). Apply spinosad + protein bait spray weekly. Collect all fallen fruit daily and bury 2 feet deep. Bag individual fruits if high-value crop.',
            'dacus dorsalis(hendel)' => 'CRITICAL: Oriental fruit fly attacks 150+ host plants. 50-100% fruit damage. IMMEDIATE ACTION: Mass trapping with methyl eugenol lures (20 traps/ha). Apply spinosad bait spray (GF-120) to foliage borders. Sanitation: remove all fallen and infested fruit within 100m radius. Bury or solarize waste.',
            'yellow rice borer' => 'CRITICAL: Causes dead hearts and white heads. 25-60% yield loss in susceptible varieties. IMMEDIATE ACTION: Apply fipronil 100g/ha or flubendiamide 100ml/ha at maximum tillering. Maintain 5cm water depth for 3 days after application. Synchronize planting dates in area to break pest cycle.',
        ];

        // High severity pests - Can cause 20-50% crop loss, urgent treatment needed within 24-48 hours
        $highPests = [
            'white backed plant hopper' => 'HIGH: Primary vector of rice tungro virus. 15-40% yield loss. URGENT: Apply buprofezin 500g/ha or pymetrozine 250g/ha. Remove weeds (virus reservoirs) within 10m of field. Use virus-resistant varieties. Economic threshold: 5-10 hoppers per plant. Scout weekly.',
            'aphids' => 'HIGH: Transmits 100+ plant viruses, causes stunting. 20-50% yield loss in vegetables/cereals. URGENT: Apply imidacloprid 100ml/ha or acetamiprid 100g/ha. Release ladybugs (500 per 100m²). Use reflective mulch. Avoid excessive nitrogen fertilizer. Economic threshold: 50 aphids per plant.',
            'rice leaf roller' => 'HIGH: Rolls leaves reducing photosynthesis. 10-30% yield loss. URGENT: Apply chlorantraniliprole 60ml/ha or flubendiamide 100ml/ha when 20% leaves show damage. Drain field 2 days before spraying. Preserve spiders and wasps (natural enemies). Peak activity: vegetative to flowering stage.',
            'thrips' => 'HIGH: Transmits tospoviruses, causes silvering and fruit scarring. 15-40% yield loss. URGENT: Apply spinosad 200ml/ha or abamectin 500ml/ha. Use blue sticky traps (10 per 100m²). Remove weeds and crop debris. Economic threshold: 5 thrips per flower or 30 per plant.',
            'cabbage army worm' => 'HIGH: Voracious feeder on cruciferous crops. 30-60% defoliation possible. URGENT: Apply Bt (Bacillus thuringiensis) 1kg/ha or emamectin benzoate 200g/ha in evening. Hand-pick egg masses (yellow, clustered). Use pheromone traps for monitoring. Economic threshold: 2 larvae per plant.',
            'beet army worm' => 'HIGH: Attacks 300+ plant species. 25-50% yield loss. URGENT: Apply spinosad 200ml/ha or indoxacarb 200ml/ha. Scout undersides of leaves for eggs. Use pheromone traps (2 per ha). Treat when 10% plants show damage. Larvae hide in soil during day.',
            'paddy stem maggot' => 'HIGH: Causes dead hearts in young seedlings. 15-35% seedling mortality. URGENT: Apply carbofuran 1kg/ha mixed with sand at transplanting. Use 15-20 day old seedlings (more resistant). Avoid early planting. Remove and destroy affected plants. Drain field if severe.',
            'small brown plant hopper' => 'HIGH: Transmits rice ragged stunt virus. 10-30% yield loss. URGENT: Apply thiamethoxam 100g/ha or dinotefuran 200g/ha. Avoid excessive nitrogen (promotes population). Maintain balanced fertilization. Economic threshold: 10-15 hoppers per plant. Scout at boot to heading stage.',
            'rice water weevil' => 'HIGH: Larvae prune roots causing stunting. 10-25% yield loss. URGENT: Maintain 7-10cm water depth for 2 weeks after transplanting (drowns eggs). Apply chlorpyrifos 500ml/ha if 20% plants show feeding scars. Delay permanent flood until plants established.',
            'flea beetle' => 'HIGH: Shot-hole damage reduces photosynthesis. 20-40% yield loss in young plants. URGENT: Apply neem oil 3ml/L or pyrethrin 200ml/ha. Use floating row covers on seedlings. Apply kaolin clay as repellent. Economic threshold: 2-3 beetles per plant or 10% leaf area damaged.',
            'mango flat beak leafhopper' => 'HIGH: Causes hopper burn, reduces flowering. 15-35% yield loss. URGENT: Apply imidacloprid 200ml/ha or thiamethoxam 100g/ha at panicle emergence. Prune affected branches. Remove alternate hosts (weeds). Two applications 15 days apart during flowering.',
            'white margined moth' => 'HIGH: Defoliates fruit trees and ornamentals. 20-40% defoliation. URGENT: Apply Bt 1kg/ha or chlorantraniliprole 60ml/ha. Hand-pick egg masses (white, hairy). Use light traps for adults. Treat when 15% leaves show damage. Larvae most active at night.',
            'prodenia litura' => 'HIGH: Tobacco cutworm attacks 120+ crops. 25-50% yield loss. URGENT: Apply Bt 1kg/ha or spinosad 200ml/ha in evening. Hand-pick larvae (hide under leaves during day). Use pheromone traps. Economic threshold: 2-3 larvae per plant. Destroy crop residue after harvest.',
        ];

        // Medium severity pests - Can cause 10-25% crop loss, treat within 3-7 days if threshold exceeded
        $mediumPests = [
            'rice leafhopper' => 'MEDIUM: Sucks sap, causes yellowing. 8-20% yield loss. ACTION: Monitor weekly. Apply buprofezin 500g/ha when 15 hoppers per plant. Maintain field hygiene. Avoid early planting. Natural enemies usually provide control.',
            'toxoptera citricidus' => 'MEDIUM: Brown citrus aphid, transmits tristeza virus. 10-30% yield loss. ACTION: Apply imidacloprid 200ml/ha or thiamethoxam 100g/ha when 10% shoots infested. Preserve ladybugs and lacewings. Prune heavily infested shoots. Monitor new growth weekly.',
            'english grain aphid' => 'MEDIUM: Feeds on wheat heads, reduces grain fill. 5-15% yield loss. ACTION: Apply pirimicarb 250g/ha or thiamethoxam 100g/ha at milk stage if 15 aphids per head. Preserve natural enemies. Economic threshold: 10-15 aphids per head from boot to dough stage.',
            'wheat blossom midge' => 'MEDIUM: Larvae destroy developing grain. 10-30% yield loss. ACTION: Apply lambda-cyhalothrin 250ml/ha at early flowering (50% heads emerged). Scout at dusk for adults. Use resistant varieties. Plow stubble to destroy pupae.',
            'red spider' => 'MEDIUM: Two-spotted spider mite causes bronzing. 10-25% yield loss. ACTION: Apply abamectin 500ml/ha or spiromesifen 600ml/ha. Increase irrigation frequency. Avoid dusty conditions. Release predatory mites (Phytoseiulus). Economic threshold: 5-10 mites per leaf.',
            'peach borer' => 'MEDIUM: Bores into trunk causing gummosis. 10-20% tree mortality over time. ACTION: Apply permethrin to trunk base (April-August). Remove gum and frass, probe tunnels. Paint trunk with white latex paint. Maintain tree vigor. Use pheromone mating disruption.',
            'grub' => 'MEDIUM: White grubs sever roots causing wilting. 10-25% plant loss. ACTION: Apply chlorpyrifos 2L/ha to soil before planting. Practice crop rotation with non-grasses. Deep plowing exposes grubs to predators. Irrigate to bring grubs to surface.',
            'mole cricket' => 'MEDIUM: Tunnels uproot seedlings. 10-20% seedling loss. ACTION: Use poison baits (2kg/ha) in evening. Flood fields overnight to drive out. Apply fipronil 500ml/ha. Plow in summer to destroy eggs. Economic threshold: 2-4 per m².',
            'wireworm' => 'MEDIUM: Larvae bore into seeds and roots. 10-20% stand loss. ACTION: Apply phorate 10kg/ha at planting. Use seed treatment (imidacloprid). Rotate with non-host crops (legumes). Plow in fall to expose larvae. Bait traps for monitoring.',
            'black cutworm' => 'MEDIUM: Cuts seedlings at soil level. 10-30% plant loss. ACTION: Apply chlorpyrifos 500ml/ha around plant base. Use cardboard collars. Scout at night with flashlight. Economic threshold: 3-5% plants cut or 1 larva per 2 plants.',
            'green bug' => 'MEDIUM: Greenbug aphid injects toxin causing dead patches. 10-30% yield loss. ACTION: Apply imidacloprid 100ml/ha when 50 aphids per plant. Use resistant varieties (e.g., Wintermalt). Preserve parasitic wasps. Scout twice weekly in spring.',
            'alfalfa weevil' => 'MEDIUM: Larvae skeletonize leaves. 15-30% yield loss in first cutting. ACTION: Apply chlorpyrifos 500ml/ha or malathion 1L/ha when 30% tips show feeding. Early harvest (before 10% bloom) if severe. Preserve natural enemies.',
            'tarnished plant bug' => 'MEDIUM: Causes cat-facing on fruit, bud abortion. 10-25% yield loss. ACTION: Apply acephate 500g/ha or bifenthrin 200ml/ha at bud stage. Remove weeds (breeding sites). Use white sticky traps. Economic threshold: 1 bug per 6 plants.',
            'blister beetle' => 'MEDIUM: Defoliates crops, toxic to livestock. 10-20% defoliation. ACTION: Hand-pick with gloves (avoid crushing). Apply carbaryl 1kg/ha if population high. Usually sporadic. Avoid feeding infested hay to animals (cantharidin toxin).',
            'pieris canidia' => 'MEDIUM: Cabbage butterfly larvae bore into heads. 15-30% yield loss. ACTION: Hand-pick caterpillars and eggs (yellow, on undersides). Apply Bt 1kg/ha or spinosad 200ml/ha. Use row covers. Economic threshold: 0.3 larvae per plant.',
            'apolygus lucorum' => 'MEDIUM: Mirid bug causes fruit drop and deformity. 10-25% yield loss. ACTION: Apply imidacloprid 200ml/ha at bud stage. Remove alternate hosts (legumes, weeds). Use pheromone traps for monitoring. Treat when 1 bug per 10 plants.',
            'toxoptera aurantii' => 'MEDIUM: Black citrus aphid, less damaging than brown. 5-15% yield loss. ACTION: Apply insecticidal soap 20ml/L or neem oil 5ml/L. Introduce parasitic wasps (Lysiphlebus). Prune water sprouts. Usually controlled by natural enemies.',
            'grain spreader thrips' => 'MEDIUM: Affects grain quality and germination. 5-15% quality loss. ACTION: Apply dimethoate 500ml/ha if population high at heading. Harvest at proper maturity (14% moisture). Dry grain quickly. Usually not economically damaging.',
            'rice shell pest' => 'MEDIUM: Storage pest, not field pest. 5-20% storage loss. ACTION: Dry grain to 12-14% moisture before storage. Use hermetic storage bags or sealed containers. Apply diatomaceous earth. Fumigate with phosphine if severe. Clean storage facilities.',
            'large cutworm' => 'MEDIUM: Feeds on young plants at night. 10-20% plant loss. ACTION: Hand-pick at night with flashlight. Apply Bt 1kg/ha or spinosad 200ml/ha. Use bait (bran + carbaryl). Remove plant debris. Economic threshold: 5% plants damaged.',
            'yellow cutworm' => 'MEDIUM: Damages seedlings and young plants. 10-20% plant loss. ACTION: Apply carbaryl 1kg/ha or permethrin 250ml/ha around plant base. Remove plant debris. Cultivate soil before planting to expose pupae. Use collars on transplants.',
            'bird cherry-oataphid' => 'MEDIUM: Aphid on cereals, usually early season. 5-15% yield loss. ACTION: Monitor population. Apply insecticidal soap 20ml/L if 10 aphids per tiller. Usually controlled by natural enemies. Avoid excessive nitrogen. Rarely requires treatment.',
            'wheat sawfly' => 'MEDIUM: Larvae bore into stems causing lodging. 10-30% yield loss. ACTION: Plant resistant varieties (solid stem). Harvest low (5cm stubble) to remove larvae. Rotate with non-cereals. Plow stubble immediately after harvest.',
            'beet fly' => 'MEDIUM: Larvae mine leaves reducing photosynthesis. 10-20% yield loss. ACTION: Remove and destroy affected leaves. Apply spinosad 200ml/ha if 30% leaves mined. Use row covers. Usually one generation per season.',
            'meadow moth' => 'MEDIUM: Larvae feed on grass and crops. 10-20% defoliation. ACTION: Apply Bt 1kg/ha or chlorantraniliprole 60ml/ha. Mow grass areas around fields. Usually sporadic. Economic threshold: 5 larvae per m².',
            'beet weevil' => 'MEDIUM: Adults and larvae damage beets. 10-25% yield loss. ACTION: Apply thiamethoxam 100g/ha at seedling stage. Practice 3-year crop rotation. Remove crop debris. Plow in fall to destroy overwintering adults.',
        ];

        // Low severity pests - Typically cause <10% loss, monitor and treat only if population exceeds threshold
        $lowPests = [
            'rice stemfly' => 'LOW: Minor pest, causes small dead hearts. <5% yield loss. ACTION: Monitor population during seedling stage. Usually controlled by natural enemies (spiders, wasps). Treatment rarely needed. Maintain field sanitation.',
            'penthaleus major' => 'LOW: Winter grain mite, feeds on seedlings. <5% yield loss. ACTION: Monitor only. Usually not economically damaging. Damage appears as silvering on leaves. Natural rainfall usually controls population.',
            'longlegged spider mite' => 'LOW: Generally beneficial predator of pest mites. ACTION: No action needed. This is a beneficial species that preys on harmful spider mites. Preserve as natural enemy.',
            'wheat phloeothrips' => 'LOW: Minor pest of wheat heads. <3% yield loss. ACTION: Monitor only. Rarely requires treatment. Usually controlled by natural enemies. Damage is cosmetic.',
            'cerodonta denticornis' => 'LOW: Leaf miner causing white trails. <5% yield loss. ACTION: Remove affected leaves if aesthetic concern. Usually minor damage. Natural parasitoids provide control.',
            'flax budworm' => 'LOW: Minor pest of flax. <5% yield loss. ACTION: Monitor population during bud stage. Treat only if 20% buds damaged. Usually sporadic occurrence.',
            'alfalfa plant bug' => 'LOW: Minor pest causing stippling. <5% yield loss. ACTION: Monitor during bud stage. Usually controlled by natural enemies. Economic threshold: 2 bugs per sweep.',
            'lytta polita' => 'LOW: Blister beetle, usually sporadic. <5% defoliation. ACTION: Hand-pick if present (wear gloves). Usually not economically significant. Avoid in hay (toxic to livestock).',
            'legume blister beetle' => 'LOW: Occasional pest on legumes. <5% defoliation. ACTION: Monitor and hand-pick if necessary. Usually sporadic. More of concern in hay production.',
            'therioaphis maculata buckton' => 'LOW: Spotted alfalfa aphid. <8% yield loss. ACTION: Monitor population. Usually controlled by predators (ladybugs, lacewings). Economic threshold: 40 aphids per stem.',
            'odontothrips loti' => 'LOW: Clover thrips. <5% yield loss. ACTION: Monitor only. Usually minor pest. Natural enemies provide adequate control.',
            'alfalfa seed chalcid' => 'LOW: Affects seed production only. <10% seed loss. ACTION: Monitor seed fields. Usually not economically significant in forage production. Use early-maturing varieties.',
            'limacodidae' => 'LOW: Slug caterpillars, stinging hairs. <5% defoliation. ACTION: Hand-pick if present (wear gloves). Usually minor. More of nuisance than economic pest.',
            'viteus vitifoliae' => 'LOW: Grape phylloxera (on resistant rootstocks). <5% yield loss. ACTION: Use resistant rootstocks (standard practice). Monitor for galls. Usually not problematic on grafted vines.',
            'colomerus vitis' => 'LOW: Grape erineum mite causes leaf galls. <5% yield loss. ACTION: Usually minor cosmetic damage. Prune affected leaves if needed. Rarely requires treatment.',
            'brevipoalpus lewisi mcgregor' => 'LOW: False spider mite. <5% yield loss. ACTION: Monitor only. Usually minor damage. Natural predators provide control.',
            'oides decempunctata' => 'LOW: Leaf beetle on various crops. <8% defoliation. ACTION: Monitor population. Hand-pick if necessary. Usually not economically significant.',
            'polyphagotars onemus latus' => 'LOW: Broad mite causes leaf distortion. <10% yield loss. ACTION: Monitor for leaf distortion. Apply miticide (abamectin) only if severe. Usually minor.',
            'pseudococcus comstocki kuwana' => 'LOW: Comstock mealybug. <8% yield loss. ACTION: Monitor and apply horticultural oil 20ml/L if population increases. Introduce natural enemies (Cryptolaemus).',
            'parathrene regalis' => 'LOW: Clearwing moth borer on grape. <5% yield loss. ACTION: Monitor for entry holes and frass. Prune affected branches. Usually sporadic.',
            'ampelophaga' => 'LOW: Hawk moth caterpillar on grape. <5% defoliation. ACTION: Hand-pick caterpillars (large, easy to spot). Usually not severe. Natural enemies provide control.',
            'lycorma delicatula' => 'LOW: Spotted lanternfly (established areas). <10% yield loss. ACTION: Scrape egg masses (Sept-June). Apply contact insecticide if population high. Remove tree of heaven (preferred host).',
            'xylotrechus' => 'LOW: Longhorn beetle on stressed trees. <5% tree mortality. ACTION: Monitor trees. Remove and destroy infested wood. Maintain tree vigor. Usually attacks weakened trees.',
            'cicadella viridis' => 'LOW: Green leafhopper. <5% yield loss. ACTION: Usually minor. Monitor only. Natural enemies provide adequate control.',
            'miridae' => 'LOW: Plant bugs, various species. <8% yield loss. ACTION: Monitor for damage (stippling, distortion). Usually minor. Treat only if threshold exceeded.',
            'trialeurodes vaporariorum' => 'LOW: Greenhouse whitefly (protected cultivation). <10% yield loss. ACTION: Apply insecticidal soap 20ml/L or neem oil 5ml/L. Use yellow sticky traps (1 per 10m²). Release Encarsia wasps.',
            'erythroneura apicalis' => 'LOW: Grape leafhopper causes stippling. <5% yield loss. ACTION: Monitor population. Usually minor. Economic threshold: 15 nymphs per leaf. Natural enemies provide control.',
            'papilio xuthus' => 'LOW: Swallowtail butterfly, usually not a pest. <3% defoliation. ACTION: Hand-pick caterpillars if needed. Usually aesthetic concern only. Often considered beneficial for pollination.',
            'panonchus citri mcgregor' => 'LOW: Citrus red mite. <8% yield loss. ACTION: Apply horticultural oil 20ml/L if needed. Usually controlled by predatory mites (Euseius). Avoid broad-spectrum insecticides.',
            'phyllocoptes oleiverus ashmead' => 'LOW: Citrus rust mite causes bronzing. <5% cosmetic damage. ACTION: Apply sulfur 3g/L or horticultural oil if severe. Usually minor. Some bronzing is normal.',
            'icerya purchasi maskell' => 'LOW: Cottony cushion scale (with biocontrol). <5% yield loss. ACTION: Introduce vedalia beetle (Rodolia cardinalis). Apply horticultural oil only if severe. Biocontrol usually sufficient.',
            'unaspis yanonensis' => 'LOW: Arrowhead scale. <8% yield loss. ACTION: Apply horticultural oil 20ml/L during dormant season. Prune heavily infested branches. Usually minor.',
            'ceroplastes rubens' => 'LOW: Red wax scale. <8% yield loss. ACTION: Apply horticultural oil 20ml/L. Prune heavily infested branches. Natural enemies (parasitic wasps) provide control.',
            'chrysomphalus aonidum' => 'LOW: Florida red scale. <8% yield loss. ACTION: Apply horticultural oil 20ml/L. Introduce parasitic wasps (Aphytis). Usually minor with biocontrol.',
            'parlatoria zizyphus lucus' => 'LOW: Black parlatoria scale. <5% yield loss. ACTION: Apply horticultural oil 20ml/L. Maintain tree vigor through proper fertilization and irrigation.',
            'nipaecoccus vastalor' => 'LOW: Mealybug on various crops. <8% yield loss. ACTION: Apply insecticidal soap 20ml/L or neem oil 5ml/L. Introduce natural enemies (Cryptolaemus, Leptomastix).',
            'aleurocanthus spiniferus' => 'LOW: Orange spiny whitefly. <10% yield loss. ACTION: Apply horticultural oil 20ml/L. Use yellow sticky traps. Natural enemies (Encarsia) provide control.',
            'bactrocera tsuneonis' => 'LOW: Fruit fly, minor species. <10% fruit damage. ACTION: Use protein bait traps. Practice field sanitation (remove fallen fruit). Usually less damaging than major fruit fly species.',
            'adristyrannus' => 'LOW: Minor pest. <5% yield loss. ACTION: Monitor only. Usually not economically significant. Natural control adequate.',
            'phyllocnistis citrella stainton' => 'LOW: Citrus leafminer (established trees). <5% yield loss. ACTION: Apply spinosad 200ml/ha or abamectin 500ml/ha on young trees only. Mature trees tolerate damage. Prune affected shoots.',
            'aphis citricola vander goot' => 'LOW: Spiraea aphid on citrus. <5% yield loss. ACTION: Apply insecticidal soap 20ml/L. Usually minor. Natural enemies provide control.',
            'scirtothrips dorsalis hood' => 'LOW: Chilli thrips causes fruit scarring. <10% cosmetic damage. ACTION: Apply spinosad 200ml/ha or abamectin 500ml/ha. Remove weeds. Use blue sticky traps.',
            'dasineura sp' => 'LOW: Gall midge species. <8% yield loss. ACTION: Prune and destroy galls. Apply insecticide only if severe. Usually minor.',
            'lawana imitata melichar' => 'LOW: Planthopper. <5% yield loss. ACTION: Monitor population. Usually minor. Natural enemies provide adequate control.',
            'salurnis marginella guerr' => 'LOW: Minor pest. <5% yield loss. ACTION: Monitor only. Usually not economically significant.',
            'deporaus marginatus pascoe' => 'LOW: Weevil. <5% yield loss. ACTION: Monitor and hand-pick if present. Usually minor occurrence.',
            'chlumetia transversa' => 'LOW: Minor pest. <5% yield loss. ACTION: Monitor population. Usually not economically significant.',
            'rhytidodera bowrinii white' => 'LOW: Longhorn beetle. <5% tree damage. ACTION: Remove and destroy infested wood. Usually attacks stressed trees. Maintain tree vigor.',
            'sternochetus frigidus' => 'LOW: Weevil. <8% yield loss. ACTION: Monitor and apply insecticide if population is high. Usually minor.',
            'cicadellidae' => 'LOW: Leafhoppers (various species). <8% yield loss. ACTION: Monitor population. Usually controlled by natural enemies. Treat only if specific threshold exceeded.',
            'sericaorient alismots chulsky' => 'LOW: Scarab beetle. <8% yield loss. ACTION: Monitor and apply insecticide if needed. Usually minor. Adults feed on leaves, larvae on roots.',
        ];

        // Check which category the pest belongs to
        foreach ($criticalPests as $pest => $action) {
            if (stripos($pestLower, $pest) !== false || stripos($pest, $pestLower) !== false) {
                return [
                    'severity' => 'critical',
                    'actions' => $action
                ];
            }
        }

        foreach ($highPests as $pest => $action) {
            if (stripos($pestLower, $pest) !== false || stripos($pest, $pestLower) !== false) {
                return [
                    'severity' => 'high',
                    'actions' => $action
                ];
            }
        }

        foreach ($mediumPests as $pest => $action) {
            if (stripos($pestLower, $pest) !== false || stripos($pest, $pestLower) !== false) {
                return [
                    'severity' => 'medium',
                    'actions' => $action
                ];
            }
        }

        foreach ($lowPests as $pest => $action) {
            if (stripos($pestLower, $pest) !== false || stripos($pest, $pestLower) !== false) {
                return [
                    'severity' => 'low',
                    'actions' => $action
                ];
            }
        }

        // Default for unknown pests
        return [
            'severity' => 'medium',
            'actions' => 'UNKNOWN PEST: Proper identification required for treatment. ACTION: 1) Collect specimen in alcohol for identification. 2) Take clear photos (top, side, close-up). 3) Contact agricultural extension service or entomologist. 4) Document: location, crop, damage type, population level. 5) Monitor daily until identified. 6) Avoid broad-spectrum pesticides until pest confirmed. Economic threshold unknown - use caution.'
        ];
    }
}
