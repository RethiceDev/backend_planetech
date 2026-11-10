<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Classe un produit dans UNE sous-catégorie (noms EXACTS de tes seeders).
 *
 * Principe : chaque sous-catégorie a des motifs pondérés. Un motif trouvé dans
 *   - le TITRE compte x3
 *   - la CATÉGORIE SOURCE (fil d'Ariane, catégorie du site) compte x2
 *   - la DESCRIPTION compte x1
 * Les motifs "out" présents dans le titre retirent 20 points (évite les faux positifs,
 * ex. "sac pour ordinateur portable" ne va pas dans Ordinateurs).
 * Si le meilleur score est < MIN_SCORE => la sous-catégorie "Divers".
 */
class ProductClassifier
{
    public const MIN_SCORE = 6;

    public function normalize(string $text): string
    {
        $t = Str::lower(Str::ascii($text));
        $t = preg_replace('~[^a-z0-9]+~', ' ', $t);

        $abbr = [
            '~\bdisj\b|\bdicj\b~'        => 'disjoncteur',
            '~\btrfo\b|\btransfo\b~'     => 'transformateur',
            '~\bcbl\b|\bcabl\b~'         => 'cable',
            '~\binter\b~'                => 'interrupteur',
            '~\bonds?\b~'                => 'onduleur',
            '~\bdiff\b~'                 => 'differentiel',
            '~\bparaf\b~'                => 'parafoudre',
            '~\bclim\b~'                 => 'climatiseur',
            '~\bcam\b~'                  => 'camera',
            '~\bimp\b~'                  => 'imprimante',
            '~\bordi\b~'                 => 'ordinateur',
        ];

        $t = preg_replace(array_keys($abbr), array_values($abbr), $t);

        return trim(preg_replace('~\s+~', ' ', $t));
    }

    /**
     * @return array{name:?string, score:int, hits:array, alts:array, ambiguous:bool}
     */
    public function classify(string $title, string $description = '', string $context = ''): array
    {
        $t = $this->normalize($title);
        $c = $this->normalize($context);
        $d = $this->normalize(mb_substr($description, 0, 700));

        $scores = [];

        foreach ($this->rules() as $name => $def) {
            $score = 0;
            $hits  = [];

            foreach ($def['in'] as $pattern => $weight) {
                $re = '~' . $pattern . '~u';
                if (preg_match($re, $t, $m)) {
                    $score += $weight * 3;
                    $hits[] = 'titre:' . trim($m[0]);
                } elseif (preg_match($re, $c, $m)) {
                    $score += $weight * 2;
                    $hits[] = 'catégorie:' . trim($m[0]);
                } elseif (preg_match($re, $d, $m)) {
                    $score += $weight;
                    $hits[] = 'description:' . trim($m[0]);
                }
            }

            if (!empty($def['out']) && preg_match('~' . $def['out'] . '~u', $t, $m)) {
                $score -= 20;
                $hits[] = 'exclu:' . trim($m[0]);
            }

            $scores[$name] = ['score' => $score, 'hits' => $hits];
        }

        // uasort est stable en PHP 8+ : à score égal, l'ordre des règles ci-dessous fait foi
        uasort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);

        $names = array_keys($scores);
        $best  = $scores[$names[0]];
        $second = $scores[$names[1]] ?? ['score' => 0];

        $alts = [];
        foreach (array_slice($names, 1, 2) as $n) {
            if ($scores[$n]['score'] > 0) {
                $alts[] = ['name' => $n, 'score' => $scores[$n]['score']];
            }
        }

        if ($best['score'] < self::MIN_SCORE) {
            return ['name' => null, 'score' => $best['score'], 'hits' => [], 'alts' => $alts, 'ambiguous' => false];
        }

        return [
            'name'      => $names[0],
            'score'     => $best['score'],
            'hits'      => array_slice($best['hits'], 0, 4),
            'alts'      => $alts,
            'ambiguous' => $second['score'] >= $best['score'] * 0.8,
        ];
    }

    /**
     * Noms EXACTS des sous-catégories de tes seeders. Motifs écrits SANS accents,
     * apostrophes/tirets = espace (le texte est normalisé avant comparaison).
     * L'ordre sert de départage en cas d'égalité.
     */
    protected function rules(): array
    {
        return [

            // ───────────────────────── SÉCURITÉ (avant Réseaux/Électricité : plus spécifique)
            'Kits Vidéosurveillance Solaires' => [
                'in'  => [
                    'kit.*solaire|solaire.*kit' => 5,
                    'camera.*solaire|solaire.*camera' => 6,
                    'videosurveillance.*solaire|solaire.*videosurveillance' => 6,
                ],
            ],
            'Interphones & Visiophones' => [
                'in'  => [
                    'interphone|visiophone|videophone|portier|platine de rue|moniteur video|kit video|sonnette video' => 5,
                    '\bkcv\b|\bkc ?\d' => 5,
                ],
            ],
            "Contrôle d'Accès & Badges" => [
                'in'  => [
                    'controle d acces|badge|lecteur (rfid|de badge|d empreinte|de carte)|biometri|empreinte|digicode|clavier a code|pointeuse|tourniquet|portillon' => 5,
                    'serrure (electrique|magnetique|connectee)|ventouse|gache|bouton de sortie|carte rfid|\brfid\b' => 4,
                ],
            ],
            'Détecteurs de Fumée & Incendie' => [
                'in'  => [
                    'detecteur de (fumee|chaleur|gaz|monoxyde)|incendie|extincteur|sprinkler|declencheur manuel|avertisseur' => 5,
                ],
            ],
            'Alarmes Anti-intrusion' => [
                'in'  => [
                    'alarme|intrusion|sirene|centrale d alarme|detecteur (de mouvement|d ouverture|volumetrique|infrarouge)|contact d ouverture|barriere infrarouge|\bpir\b' => 5,
                ],
                'out' => 'incendie|fumee',
            ],
            'Portails Automatiques & Barrières' => [
                'in'  => [
                    'portail|barriere (automatique|levante)|motorisation|moteur (de )?(portail|coulissant|battant)|automatisme|porte (automatique|de garage)|rideau metallique|bollard|ouvre portail' => 5,
                ],
            ],
            'Onduleurs & Sécurité Électrique' => [
                'in'  => [
                    // Onduleur dédié à la sécurité (caméras, alarme...). Sinon => Alimentation et Onduleurs
                    'onduleur.*(camera|videosurveillance|cctv|alarme|securite|dvr|nvr)|(camera|videosurveillance|cctv|alarme).*onduleur' => 6,
                ],
            ],
            'Caméras de Surveillance (CCTV)' => [
                'in'  => [
                    '\bcameras?\b' => 3,
                    'cctv|\bdome\b|\bbullet\b|\bdvr\b|\bahd\b|hdcvi|\btvi\b|enregistreur numerique|videosurveillance|surveillance' => 4,
                    'kit (de )?(videosurveillance|camera)' => 3,
                ],
                'out' => '\bip\b|\bnvr\b|\bpoe\b|wi ?fi|solaire|\b4g\b|support|boitier de jonction|alimentation',
            ],

            // ───────────────────────── RÉSEAUX & TÉLÉCOMS
            'Vidéosurveillance IP' => [
                'in'  => [
                    'camera.*\bip\b|\bip\b.*camera|\bnvr\b|camera reseau|\bip camera|\bptz\b' => 8,
                    'camera.*wi ?fi|camera.*\bpoe\b' => 5,
                ],
                'out' => 'solaire|support',
            ],
            'Fibre Optique' => [
                'in'  => [
                    'fibre|ftth|\bsfp\b|transceiver|pigtail|\bpto\b|\broe\b|\bodf\b|\bolt\b|\bont\b|coupleur optique|splitter optique|tiroir optique|boitier de raccordement optique|cable drop|rosace optique' => 6,
                    'optique|\bsc ?apc\b|\bsc ?upc\b|\blc ?upc\b|media converter|convertisseur de media' => 4,
                ],
                'out' => 'souris|lecteur|detecteur|clavier|fumee|lunette|verre',
            ],
            'Câbles Réseau et Télécoms' => [
                'in'  => [
                    'cat ?[5-8]|\butp\b|\bftp\b|\bsftp\b|ethernet|cable reseau|cable lan|cable telephonique|cable telecom|multipaire|4 paires|\brg ?(59|6|11)\b|cable coaxial|bobine reseau' => 5,
                    'cable' => 1,
                ],
                'out' => 'patch ?cord|jarretiere|cordon|\bhdmi\b|\bvga\b|\busb\b|cable d alimentation',
            ],
            'Connectique, Prises et Brassage' => [
                'in'  => [
                    'rj ?45|rj ?11|keystone|patch ?cord|jarretiere|cordon de brassage|panneau de brassage|patch panel|prise (reseau|telephone|rj)|plastron|boitier de sol|bloc de brassage|brassage' => 5,
                ],
                'out' => 'baie|rack|armoire|testeur|sertir',
            ],
            'Baies et Coffrets de Brassage' => [
                'in'  => [
                    '\bbaie\b|\brack\b|coffret (de )?brassage|coffret (reseau|vdi)|armoire (reseau|serveur)|19 pouces|\b\d{1,2} ?u\b|bandeau|\bpdu\b|passe cable|tablette pour baie' => 5,
                ],
            ],
            'Équipements Actifs (Switchs, Routeurs, Wi-Fi)' => [
                'in'  => [
                    'switch|routeur|router|point d acces|access point|pare feu|firewall|repeteur|extender|\bcpl\b|powerline|\bmodem\b|\bmesh\b|controleur wi ?fi' => 5,
                    'wi ?fi|mikrotik|ubiquiti|unifi|tp ?link|tenda|cisco|\bpoe\b|injecteur|\blte\b|\b4g\b|\b5g\b|\bcpe\b|nanostation|carte reseau|antenne wi ?fi|adaptateur wi ?fi' => 3,
                ],
                'out' => 'hdmi|\bkvm\b|\bvga\b|camera',
            ],
            'Téléphonie et Communication' => [
                'in'  => [
                    'telephone|telephonie|standard telephonique|\bpabx\b|\bipbx\b|ip phone|poste (ip|fixe|telephonique)|\bvoip\b|\bsip\b|\bgsm\b|talkie|walkie|\bdect\b|yealink|grandstream|fanvil|casque telephonique|micro casque|conference' => 4,
                    'smartphone|iphone|galaxy|tecno|infinix|itel' => 4,
                ],
                'out' => 'cable|prise|rj ?11|camera|interphone|visiophone|ordinateur|support',
            ],
            'Antennes, TV et Satellite' => [
                'in'  => [
                    'antenne|parabole|\blnb\b|satellite|decodeur|\btnt\b|tv box|android tv|televiseur|smart tv|\btv\b|demodulateur|amplificateur d antenne|multiswitch|dstv|startimes|recepteur numerique' => 4,
                ],
                'out' => 'wi ?fi|\b4g\b|\blte\b|\bcpe\b|nanostation|camera|alarme',
            ],
            'Outillage et Mesure Réseau' => [
                'in'  => [
                    'testeur|pince a (sertir|denuder|poinconner)|pince coupante|sertir|denuder|\botdr\b|power meter|cliveuse|soudeuse|stylo optique|\bvfl\b|localisateur|generateur de tons|tone generator|lan tester|punch ?down|poinconneuse|kit d outils|valise' => 5,
                ],
            ],

            // ───────────────────────── FROID & CLIMATISATION
            'Gaz Réfrigérants (R410A, R32, R134a)' => [
                'in'  => [
                    '\br ?(410a|32|134a|404a|407c|22|600a|290)\b|gaz refrigerant|fluide frigorigene|bouteille de gaz|refrigerant' => 6,
                ],
                'out' => 'climatiseur|split|btu|compresseur|cassette|armoire',
            ],
            'Compresseurs Frigorifiques' => [
                'in'  => [
                    'compresseur' => 6,
                    'copeland|danfoss|bitzer|tecumseh|scroll|hermetique' => 2,
                ],
                'out' => 'compresseur d air|compresseur a air|gonfleur|pompe a air',
            ],
            'Tubes et Isolants Cuivre' => [
                'in'  => [
                    'tube (en )?cuivre|tuyau cuivre|rouleau cuivre|cuivre frigorifique|tubes? isol|armaflex|gaine isolante|manchon isolant|mousse isolante|isolant' => 5,
                ],
                'out' => 'cable|cuivre nu|isolateur',
            ],
            'Chambres Froides (Positives & Négatives)' => [
                'in'  => [
                    'chambres? froides?|panneaux? (frigorifiques?|sandwich)|groupe frigorifique|groupe de froid|evaporateur|unite de condensation|centrale frigorifique|vitrine refrigeree|congelateur' => 6,
                    'positive|negative' => 1,
                ],
            ],
            'Climatiseurs Cassettes' => [
                'in'  => [
                    'cassette|4 voies|quatre voies' => 5,
                ],
                'out' => 'fibre|optique|jarretiere|audio',
            ],
            'Climatiseurs Armoires' => [
                'in'  => [
                    '(climatis|btu).*(armoire|colonne)|(armoire|colonne).*(climatis|btu)' => 7,
                    'sur pied|standing' => 2,
                ],
                'out' => 'electrique|reseau|serveur|brassage',
            ],
            'Pièces Détachées & Accessoires' => [
                'in'  => [
                    'telecommande (pour )?(clim|climatiseur|split)|condensateur|support (de |pour )?(clim|climatiseur|split|groupe|unite)|pompe de relevage|pressostat|detendeur|filtre deshydrateur|manometre|flexible gaz|ventilateur (de )?condenseur|kit d installation|sonde|thermostat' => 4,
                ],
            ],
            'Climatiseurs Split' => [
                'in'  => [
                    '\bsplit\b' => 4,
                    'climatiseur|climatis' => 2,
                    'btu' => 2,
                    '\bmural\b|\binverter\b' => 1,
                ],
                'out' => 'armoire|colonne|cassette|gainable|support|telecommande|bouteille|compresseur|tube|cuivre|isolant',
            ],

            // ───────────────────────── INFORMATIQUE & BUREAUTIQUE
            'Logiciels et Licences' => [
                'in'  => [
                    'licence|logiciel|antivirus|microsoft 365|office 20\d\d|windows (10|11|server)|kaspersky|eset|norton|bitdefender|mcafee|adobe|autocad|abonnement' => 4,
                ],
                'out' => '\b(ordinateur|laptop|desktop|tablette|imprimante)\b|pc portable',
            ],
            'Impression et Consommables' => [
                'in'  => [
                    'imprimante|toner|cartouche|encre|ruban (d )?(impression|imprimante|encreur)|papier|photocopieur|multifonction|traceur|etiqueteuse|tambour|\bdrum\b|rouleau thermique' => 4,
                ],
            ],
            'Stockage et Sauvegarde' => [
                'in'  => [
                    'disque dur|\bhdd\b|\bssd\b|cle usb|clef usb|flash ?drive|carte sd|micro ?sd|memory card|\bnas\b|sauvegarde|backup|disque externe|lecteur de carte|bande lto|dvd vierge' => 5,
                ],
                'out' => '^(sac|housse|support|boitier)',
            ],
            'Composants Informatiques' => [
                'in'  => [
                    'carte mere|processeur|\bcpu\b|\bgpu\b|carte graphique|barrette|ddr ?[345]|memoire vive|alimentation pc|boitier pc|ventirad|ventilateur pc|watercooling|pate thermique|nvme' => 5,
                ],
            ],
            'Alimentation et Onduleurs' => [
                'in'  => [
                    'onduleur|\bups\b|parasurtenseur|stabilisateur|regulateur de tension|batterie (onduleur|de secours)|power ?bank|bloc d alimentation|alimentation (stabilisee|secourue|12v)' => 4,
                ],
            ],
            'Écrans et Périphériques' => [
                'in'  => [
                    'ecran|moniteur|clavier|souris|webcam|casque|\bmicro\b|haut parleur|enceinte|videoprojecteur|projecteur video|manette|tapis de souris|lecteur code barre|scanner|tablette graphique|barre de son|ecouteurs|oreillette' => 3,
                ],
                'out' => 'casque de (chantier|securite)|film protecteur|ecran de protection|micro ?sd|micro ?onde|casque telephonique',
            ],
            'Accessoires et Connectique PC' => [
                'in'  => [
                    'cable hdmi|\bhdmi\b|\bvga\b|displayport|\busb\b|adaptateur|convertisseur|\bhub\b|\bsac\b|housse|sacoche|sac a dos|refroidisseur|station d accueil|\bdock\b|chargeur|\botg\b|dongle|bluetooth|\bkvm\b|splitter' => 3,
                ],
                'out' => 'cle usb|clef usb|flash|disque|wi ?fi',
            ],
            'Ordinateurs Portables et PC de Bureau' => [
                'in'  => [
                    'ordinateur|laptop|pc portable|notebook|macbook|imac|mac mini|all in one|tout en un|pc de bureau|unite centrale|workstation|chromebook|thinkpad|latitude|inspiron|probook|elitebook|vostro|pavilion|ideapad|zbook|optiplex|thinkcentre|desktop' => 4,
                    '\bpc\b' => 2,
                    'core i[3579]|ryzen|celeron' => 1,
                ],
                'out' => '^(sac|housse|chargeur|adaptateur|batterie|support|refroidisseur|souris|clavier|ecran|moniteur|webcam|casque|imprimante|onduleur|station|sacoche|cartable|dock)',
            ],

            // ───────────────────────── ÉLECTRICITÉ GÉNÉRALE
            'Transformateurs' => [
                'in'  => [
                    'transformateur' => 6,
                    '\btc\b|transformateur de courant' => 3,
                    '\bkva\b' => 2,
                ],
                'out' => 'compteur|kcv|visiophone|interphone|platine|moniteur|camera|adaptateur|chargeur|sonnette',
            ],
            'Matériel HT (Haute Tension) / MT (Moyenne Tension)' => [
                'in'  => [
                    'haute tension|moyenne tension|poste de transformation' => 3,
                    '\bht\b|\bmt\b' => 1,
                    '\d+ ?kv\b' => 3,
                    '\bcellule\b|sectionneur (ht|mt|aerien)|interrupteur aerien|isolateur|chaine d isolateur|parafoudre (ht|mt|oxyde)' => 4,
                    'ligne aerienne|materiel de ligne' => 1,
                ],
                'out' => 'photoelectrique|photovoltaique|solaire|compteur|visiophone',
            ],
            'Accessoires de Pose, Fixation et Raccordement' => [
                'in'  => [
                    '\bpince\b|cosse|embout|collier|gaine|raccord|manchon|boite de (derivation|jonction)|ruban (adhesif|isolant)|chemins? de cable|goulotte|moulure|presse etoupe|serre cable|support de (barre|cable)|piquet de terre|\bdomino|wago|haut de poteau|bloc de (doublement|terre)|accessoire de chaine|berceau' => 5,
                    'attache|bride|etrier|crochet|visiere|console|connecteur|cheville|\bvis\b|conduit|\bicta\b|\birl\b|fixation' => 3,
                ],
                'out' => 'fibre|optique|sfp|rj ?45|rj ?11|climatis|frigorifique|gaz|camera|ecran|ordinateur|laptop|serveur|cat ?[5-8]|\btv\b|televiseur|antenne|parabole|alarme|visiophone|interphone|imprimante|sertir|denuder|testeur',
            ],
            'Câbles et Conducteurs' => [
                'in'  => [
                    '\bcables?\b|conducteur' => 3,
                    'u ?1000|\bh0[57]|\bar2v|\br2v|torsade|cuivre nu|almelec|\bnyy\b|fil electrique|fil souple|fil rigide|h1z2z2|cable solaire' => 3,
                ],
                'out' => 'rj ?45|cat ?[5-8]|\butp\b|\bftp\b|reseau|ethernet|telephon|coaxial|\brg ?(6|11|59)\b|fibre|optique|hdmi|vga|usb|jarretiere|patch|pince|cosse|gaine|chemin|goulotte|support|attache|presse etoupe|serre cable|passe cable|collier|manchon|embout|alimentation|rallonge|camera|alarme|incendie|interphone|haut parleur|audio|\bsfp\b',
            ],
            'Appareillage et Accessoires Domestiques / Tertiaires' => [
                'in'  => [
                    'prise murale|prise de courant|prise 2p|prise simple|prise double|prise usb|\bprises?\b|multiprise|rallonge' => 2,
                    'interrupteur|va et vient|variateur de lumiere|gradateur|douille|boite d encastrement|plaque de finition|enjoliveur|sonnette|domotique|knx|hager|legrand|schneider|simon|niko|detecteur de presence' => 3,
                    'lustre|guirlande|bande lumineuse|ruban led|projecteur|lampe|applique|plafonnier|\bspot\b|hublot|ampoule|eclairage|luminaire|\bled\b|lampadaire|reglette|panneau led|tube led|lanterne|torche|candelabre|ventilateur' => 3,
                ],
                'out' => 'rj ?45|rj ?11|telephon|hdmi|reseau|brassage|fibre|aerien|differentiel|disjoncteur|industrielle|\bcee\b|camera|alarme|videoprojecteur|projecteur video|ordinateur|interphone|visiophone',
            ],
            'Matériel BT (Basse Tension)' => [
                'in'  => [
                    'disjoncteur|differentiel|contacteur|telerupteur|relais|fusible|sectionneur|variateur|demarreur|delesteur|inverseur|minuterie|horloge programmable|compteur|multimetre|voltmetre|amperemetre|wattmetre|bouton poussoir|voyant|arret d urgence|parafoudre|groupe electrogene|alternateur|coupe circuit' => 4,
                    'tableau (electrique|de distribution|divisionnaire)|coffret|armoire electrique|bornier|rail din' => 3,
                    'prise industrielle|prise cee|fiche industrielle|\btgbt\b' => 4,
                ],
                'out' => 'optique|brassage|rj ?45|reseau|baie|serveur|alarme|incendie|camera|visiophone|videophone|interphone|\bkcv\b|climatis|chambre froide',
            ],
        ];
    }
}
