-- ============================================================================
-- Add Common Name Column to Pest Config
-- Makes pest names more understandable for Filipino users
-- ============================================================================

USE farm_database;

-- Add common_name column
ALTER TABLE pest_config 
ADD COLUMN common_name VARCHAR(150) NULL AFTER pest_name,
ADD INDEX idx_common_name (common_name);

-- Update existing records with proper Filipino translations
UPDATE pest_config SET common_name = 'Tagapagkulong ng dahon ng palay' WHERE pest_name = 'rice leaf roller';
UPDATE pest_config SET common_name = 'Uod sa dahon ng palay' WHERE pest_name = 'rice leaf caterpillar';
UPDATE pest_config SET common_name = 'Uod sa tangkay ng palay' WHERE pest_name = 'paddy stem maggot';
UPDATE pest_config SET common_name = 'Uod tagabutas ng palay (Asyatik)' WHERE pest_name = 'asiatic rice borer';
UPDATE pest_config SET common_name = 'Dilaw na uod tagabutas ng palay' WHERE pest_name = 'yellow rice borer';
UPDATE pest_config SET common_name = 'Gal-midge ng palay / Lamok-lamok ng palay' WHERE pest_name = 'rice gall midge';
UPDATE pest_config SET common_name = 'Langaw sa tangkay ng palay' WHERE pest_name = 'Rice Stemfly';
UPDATE pest_config SET common_name = 'Kayumangging sipsip-dahon ng palay' WHERE pest_name = 'brown plant hopper';
UPDATE pest_config SET common_name = 'Puting likod na sipsip-dahon' WHERE pest_name = 'white backed plant hopper';
UPDATE pest_config SET common_name = 'Maliit na kayumangging sipsip-dahon' WHERE pest_name = 'small brown plant hopper';
UPDATE pest_config SET common_name = 'Salagubang-tubig ng palay' WHERE pest_name = 'rice water weevil';
UPDATE pest_config SET common_name = 'Tagtalon-dahon ng palay' WHERE pest_name = 'rice leafhopper';
UPDATE pest_config SET common_name = 'Kulisap tagakalat ng butil' WHERE pest_name = 'grain spreader thrips';
UPDATE pest_config SET common_name = 'Balat-butil na pesteng palay' WHERE pest_name = 'rice shell pest';
UPDATE pest_config SET common_name = 'Uod-lupa / Bulating salagubang' WHERE pest_name = 'grub';
UPDATE pest_config SET common_name = 'Kamaro / Cricket sa lupa' WHERE pest_name = 'mole cricket';
UPDATE pest_config SET common_name = 'Alupihan ng ugat' WHERE pest_name = 'wireworm';
UPDATE pest_config SET common_name = 'Puting-margeng gamu-gamo' WHERE pest_name = 'white margined moth';
UPDATE pest_config SET common_name = 'Itim na uod-tagaputol' WHERE pest_name = 'black cutworm';
UPDATE pest_config SET common_name = 'Malaking uod-tagaputol' WHERE pest_name = 'large cutworm';
UPDATE pest_config SET common_name = 'Dilaw na uod-tagaputol' WHERE pest_name = 'yellow cutworm';
UPDATE pest_config SET common_name = 'Pulang gagamba / pulang hama' WHERE pest_name = 'red spider';
UPDATE pest_config SET common_name = 'Uod-tagabutas ng mais' WHERE pest_name = 'corn borer';
UPDATE pest_config SET common_name = 'Uod-hukbo' WHERE pest_name = 'army worm';
UPDATE pest_config SET common_name = 'Kuto ng halaman' WHERE pest_name = 'aphids';
UPDATE pest_config SET common_name = 'Salagubang sa palay (Potosia type)' WHERE pest_name = 'Potosiabre vitarsis';
UPDATE pest_config SET common_name = 'Uod sa melokoton (peach borer)' WHERE pest_name = 'peach borer';
UPDATE pest_config SET common_name = 'Kuto ng butil (Ingles na uri)' WHERE pest_name = 'english grain aphid';
UPDATE pest_config SET common_name = 'Berdeng kuto ng halaman' WHERE pest_name = 'green bug';
UPDATE pest_config SET common_name = 'Kuto ng trigo at oats' WHERE pest_name = 'bird cherry-oataphid';
UPDATE pest_config SET common_name = 'Lamok ng bulaklak ng trigo' WHERE pest_name = 'wheat blossom midge';
UPDATE pest_config SET common_name = 'Pulang gagamba sa trigo' WHERE pest_name = 'penthaleus major';
UPDATE pest_config SET common_name = 'Habang-paa na hama' WHERE pest_name = 'longlegged spider mite';
UPDATE pest_config SET common_name = 'Kulisap sa tangkay ng trigo' WHERE pest_name = 'wheat phloeothrips';
UPDATE pest_config SET common_name = 'Langaw na tagabutas ng trigo' WHERE pest_name = 'wheat sawfly';
UPDATE pest_config SET common_name = 'Langaw ng damo / rice fly' WHERE pest_name = 'cerodonta denticornis';
UPDATE pest_config SET common_name = 'Langaw ng beet' WHERE pest_name = 'beet fly';
UPDATE pest_config SET common_name = 'Lukso-salagubang' WHERE pest_name = 'flea beetle';
UPDATE pest_config SET common_name = 'Uod-hukbo ng repolyo' WHERE pest_name = 'cabbage army worm';
UPDATE pest_config SET common_name = 'Uod-hukbo ng beet' WHERE pest_name = 'beet army worm';
UPDATE pest_config SET common_name = 'Langaw na tagabuo ng batik sa beet' WHERE pest_name = 'Beet spot flies';
UPDATE pest_config SET common_name = 'Gamu-gamong parang' WHERE pest_name = 'meadow moth';
UPDATE pest_config SET common_name = 'Salagubang ng beet' WHERE pest_name = 'beet weevil';
UPDATE pest_config SET common_name = 'Oriental na salagubang' WHERE pest_name = 'sericaorient alismots chulsky';
UPDATE pest_config SET common_name = 'Salagubang ng alfalfa' WHERE pest_name = 'alfalfa weevil';
UPDATE pest_config SET common_name = 'Uod sa usbong ng flax' WHERE pest_name = 'flax budworm';
UPDATE pest_config SET common_name = 'Kulisap sa alfalfa' WHERE pest_name = 'alfalfa plant bug';
UPDATE pest_config SET common_name = 'Maduming kulisap ng halaman' WHERE pest_name = 'tarnished plant bug';
UPDATE pest_config SET common_name = 'Tipaklong' WHERE pest_name = 'Locustoidea';
UPDATE pest_config SET common_name = 'Blister beetle (salagubang na nagdudulot ng paltos)' WHERE pest_name = 'lytta polita';
UPDATE pest_config SET common_name = 'Paltos-salagubang sa munggo' WHERE pest_name = 'legume blister beetle';
UPDATE pest_config SET common_name = 'Salagubang-paltos' WHERE pest_name = 'blister beetle';
UPDATE pest_config SET common_name = 'Kuto ng alfalfa' WHERE pest_name = 'therioaphis maculata Buckton';
UPDATE pest_config SET common_name = 'Thrips sa munggo' WHERE pest_name = 'odontothrips loti';
UPDATE pest_config SET common_name = 'Kulisap / Tripes' WHERE pest_name = 'Thrips';
UPDATE pest_config SET common_name = 'Kulisap ng binhi ng alfalfa' WHERE pest_name = 'alfalfa seed chalcid';
UPDATE pest_config SET common_name = 'Puting paru-paro ng repolyo' WHERE pest_name = 'Pieris canidia';
UPDATE pest_config SET common_name = 'Kulisap sa bulak / cotton bug' WHERE pest_name = 'Apolygus lucorum';
UPDATE pest_config SET common_name = 'Uod-balat / slug caterpillar' WHERE pest_name = 'Limacodidae';
UPDATE pest_config SET common_name = 'Kulisap ng ubas' WHERE pest_name = 'Viteus vitifoliae';
UPDATE pest_config SET common_name = 'Hama sa ubas' WHERE pest_name = 'Colomerus vitis';
UPDATE pest_config SET common_name = 'Pulang mite ng prutas' WHERE pest_name = 'Brevipoalpus lewisi McGregor';
UPDATE pest_config SET common_name = 'Salagubang ng dahon' WHERE pest_name = 'oides decempunctata';
UPDATE pest_config SET common_name = 'Broad mite / Hama sa dahon' WHERE pest_name = 'Polyphagotars onemus latus';
UPDATE pest_config SET common_name = 'Mealybug / Malagkit na kuto' WHERE pest_name = 'Pseudococcus comstocki Kuwana';
UPDATE pest_config SET common_name = 'Uod-tagabutas ng puno' WHERE pest_name = 'parathrene regalis';
UPDATE pest_config SET common_name = 'Uod ng ubas (Ampelophaga)' WHERE pest_name = 'Ampelophaga';
UPDATE pest_config SET common_name = 'Spotted lanternfly / Lanternfly ng ubas' WHERE pest_name = 'Lycorma delicatula';
UPDATE pest_config SET common_name = 'Bukbok ng kahoy' WHERE pest_name = 'Xylotrechus';
UPDATE pest_config SET common_name = 'Berdeng leafhopper' WHERE pest_name = 'Cicadella viridis';
UPDATE pest_config SET common_name = 'Kulisap ng halaman (Mirid bug)' WHERE pest_name = 'Miridae';
UPDATE pest_config SET common_name = 'Whitefly / Puting langaw' WHERE pest_name = 'Trialeurodes vaporariorum';
UPDATE pest_config SET common_name = 'Red-tipped leafhopper' WHERE pest_name = 'Erythroneura apicalis';
UPDATE pest_config SET common_name = 'Paru-parong dilaw (Swallowtail)' WHERE pest_name = 'Papilio xuthus';
UPDATE pest_config SET common_name = 'Red mite ng dalandan' WHERE pest_name = 'Panonchus citri McGregor';
UPDATE pest_config SET common_name = 'Olive mite' WHERE pest_name = 'Phyllocoptes oleiverus ashmead';
UPDATE pest_config SET common_name = 'Cottony cushion scale / Kuto-buhok' WHERE pest_name = 'Icerya purchasi Maskell';
UPDATE pest_config SET common_name = 'Scale insect ng dalandan' WHERE pest_name = 'Unaspis yanonensis';
UPDATE pest_config SET common_name = 'Pulang scale insect' WHERE pest_name = 'Ceroplastes rubens';
UPDATE pest_config SET common_name = 'Armored scale insect' WHERE pest_name = 'Chrysomphalus aonidum';
UPDATE pest_config SET common_name = 'Kuto ng jujube / zizyphus' WHERE pest_name = 'Parlatoria zizyphus Lucus';
UPDATE pest_config SET common_name = 'Nipa mealybug / Mealybug ng niyog' WHERE pest_name = 'Nipaecoccus vastalor';
UPDATE pest_config SET common_name = 'Blackfly ng sitrus' WHERE pest_name = 'Aleurocanthus spiniferus';
UPDATE pest_config SET common_name = 'Prutas na langaw (Fruit fly)' WHERE pest_name = 'Tetradacus c Bactrocera minax';
UPDATE pest_config SET common_name = 'Langaw ng prutas / Mango fruit fly' WHERE pest_name = 'Dacus dorsalis(Hendel)';
UPDATE pest_config SET common_name = 'Langaw ng prutas (Tsuneo type)' WHERE pest_name = 'Bactrocera tsuneonis';
UPDATE pest_config SET common_name = 'Uod-hukbo ng gulay' WHERE pest_name = 'Prodenia litura';
UPDATE pest_config SET common_name = 'Uod ng kahoy / wood borer' WHERE pest_name = 'Adristyrannus';
UPDATE pest_config SET common_name = 'Leaf miner ng sitrus' WHERE pest_name = 'Phyllocnistis citrella Stainton';
UPDATE pest_config SET common_name = 'Itim na kuto ng sitrus' WHERE pest_name = 'Toxoptera citricidus';
UPDATE pest_config SET common_name = 'Kuto ng dahon ng dalandan' WHERE pest_name = 'Toxoptera aurantii';
UPDATE pest_config SET common_name = 'Kuto ng prutas-sitrus' WHERE pest_name = 'Aphis citricola Vander Goot';
UPDATE pest_config SET common_name = 'Thrips ng sitrus' WHERE pest_name = 'Scirtothrips dorsalis Hood';
UPDATE pest_config SET common_name = 'Gall midge / Lamok-lamok sa usbong' WHERE pest_name = 'Dasineura sp';
UPDATE pest_config SET common_name = 'Puting planthopper ng mangga' WHERE pest_name = 'Lawana imitata Melichar';
UPDATE pest_config SET common_name = 'Kulisap ng mangga' WHERE pest_name = 'Salurnis marginella Guerr';
UPDATE pest_config SET common_name = 'Weevil ng mangga' WHERE pest_name = 'Deporaus marginatus Pascoe';
UPDATE pest_config SET common_name = 'Uod ng mangga (mango leaf caterpillar)' WHERE pest_name = 'Chlumetia transversa';
UPDATE pest_config SET common_name = 'Patag-ilong na leafhopper ng mangga' WHERE pest_name = 'Mango flat beak leafhopper';
UPDATE pest_config SET common_name = 'Bukbok ng mangga' WHERE pest_name = 'Rhytidodera bowrinii white';
UPDATE pest_config SET common_name = 'Butas-butil ng mangga' WHERE pest_name = 'Sternochetus frigidus';
UPDATE pest_config SET common_name = 'Pamilyang leafhopper / Tagtalon-dahon' WHERE pest_name = 'Cicadellidae';

-- Show updated records
SELECT pest_name, common_name, severity 
FROM pest_config 
ORDER BY severity DESC, pest_name ASC 
LIMIT 20;

-- ============================================================================
-- Migration Complete!
-- Common names added for better user understanding
-- ============================================================================
