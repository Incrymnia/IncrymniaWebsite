<?php
/**
 * Emberforge – site configuration and content.
 *
 * Everything you are likely to edit lives here: business details, build types,
 * gallery entries, services, FAQ and the quote-form options.
 * Anything marked REPLACE is placeholder text.
 */

declare(strict_types=1);

$config = [

    /* ------------------------------------------------------------------
     * Business details
     * ---------------------------------------------------------------- */
    'site' => [
        'name'        => 'Incrymnia',                       // REPLACE with your business name
        'tagline'     => 'Professional Systems for Gaming, Workstations and Servers',
        'description' => 'Custom-built gaming PCs, study computers, workstations and NAS or storage servers. Assembled, cable-managed and stress-tested before delivery. Request a free quote.',

        'email'       => 'info@incrymnia.com',                // REPLACE – shown on the site
        'quote_to'    => 'quotes@incrymnia.com',                // REPLACE – where quote requests are emailed
        'mail_from'   => 'incrymniapc@gmail.com',             // REPLACE – should be an address on your own domain
        'phone'       => '0485673647',                   // REPLACE
        'phone_href'  => '+61485673647',                                 // REPLACE e.g. '+61400000000' (leave empty to hide the link)
        'address'     => 'Central and South East Melbourne',         // REPLACE
        'hours'       => [
            ['Monday to Friday', '9:00 am to 5:30 pm'],
            ['Saturday',         '10:00 am to 2:00 pm'],
            ['Sunday',           'Closed'],
        ],

        'reply_time'     => 'one to two business days',              // shown after a quote request is sent
        'warranty_months' => 18,                               // REPLACE with your real warranty
        'build_days'     => '5 to 7 business days',                        // REPLACE with your real typical build time
    ],

    /* ------------------------------------------------------------------
     * Email delivery (Gmail SMTP)
     * Do NOT put the password here. Copy config.local.example.php to
     * config.local.php and fill it in there (that file is kept private).
     * ---------------------------------------------------------------- */
    'smtp' => [
        'enabled'     => true,
        'host'        => 'smtp.gmail.com',
        'port'        => 465,
        'encryption'  => 'ssl',            // 'ssl' with port 465, or 'tls' with port 587
        'username'    => '',               // set in config.local.php
        'password'    => '',               // set in config.local.php (Gmail App Password)
        'verify_peer' => true,
        'timeout'     => 12,
    ],

    /* ------------------------------------------------------------------
     * Hero stats (REPLACE with real numbers, or delete the ones you don't want)
     * ---------------------------------------------------------------- */
    'stats' => [
        ['value' => 800, 'suffix' => '+',     'label' => 'Systems built',          'note' => 'Experience with Gaming, study, workstation and servers'],
        ['value' => 18,   'suffix' => ' mths',  'label' => 'Warranty on every build', 'note' => 'Parts and labour'],
        ['value' => 7,   'suffix' => ' days', 'label' => 'Typical build time',      'note' => 'From the day parts arrive'],
        ['value' => 24,  'suffix' => ' hrs',  'label' => 'Stress testing',          'note' => 'Before anything ships'],
    ],

    /* ------------------------------------------------------------------
     * Build types
     * profile: relative weighting (0-100) for CPU, GPU, Memory, Storage, Quiet
     * rows:    [component, note, priority (High|Medium|Low)]
     * ---------------------------------------------------------------- */
    'profile_labels' => ['CPU', 'GPU', 'Memory', 'Storage', 'Networking'],

    'builds' => [
        'gaming' => [
            'label'   => 'Gaming PCs',
            'short'   => 'Gaming',
            'icon'    => 'gamepad',
            'summary' => 'High frame rates at your monitor’s resolution, with airflow and fan curves tuned so it stays fast and calm under load.',
            'points'  => [
                'Graphics card matched to your screen and games',
                'Tuned cooling and quiet fan curves',
                'Black, white or full RGB, your call',
            ],
            'focus'   => 'Most of the budget goes to the graphics card, then the processor.',
            'profile' => [70, 95, 60, 65, 50],
            'rows'    => [
                ['Graphics card', 'Sized to your monitor and the games you play', 'High'],
                ['Processor',     'Fast cores for steady frame rates',            'Medium'],
                ['Memory',        '16 GB as a comfortable starting point',        'Medium'],
                ['Case and cooling', 'Quiet cooling with additional RGB lighting',    'Low'],
            ],
        ],
        'study' => [
            'label'   => 'Office and study PCs',
            'short'   => 'Office & Study',
            'icon'    => 'cap',
            'summary' => 'Reliable, quiet computers for the office, homework, uni and working from home. Fast to start, easy on the power bill.',
            'points'  => [
                'Quick SSD so it boots in seconds',
                'Quiet enough for a bedroom or library desk',
                'Wi-Fi, Office and parental controls set up',
            ],
            'focus'   => 'Fast storage and quiet running matter more than raw power.',
            'profile' => [40, 25, 50, 55, 40],
            'rows'    => [
                ['Storage',   'A fast SSD makes everything feel quicker',     'High'],
                ['Memory',     'High memory to assist with multitasking',                  'Medium'],
                ['Processor', 'Plenty for office, browsers and video calls',  'Medium'],
                ['Graphics',  'Built-in graphics or a discrete graphics card for graphical performance',         'Low'],
            ],
        ],
        'workstation' => [
            'label'   => 'Workstations',
            'short'   => 'Workstation',
            'icon'    => 'monitor',
            'summary' => 'Video editing, 3D, CAD, development and data work. Built around the software you run, so renders and compiles finish sooner.',
            'points'  => [
                'High core-count CPUs and 64 GB+ of memory',
                'Fast scratch drives plus bulk storage',
                'ECC memory and pro GPUs where they matter',
            ],
            'focus'   => 'Cores and memory come first, so exports and compiles finish sooner.',
            'profile' => [95, 75, 95, 85, 75],
            'rows'    => [
                ['Processor', 'High core count for renders and compiles',    'High'],
                ['Memory',    '64 GB and up, ECC where it matters',          'High'],
                ['Graphics',  'Picked for your software, not for the box',   'Medium'],
                ['Storage',   'Fast scratch NVMe plus bulk drives',          'Medium'],
            ],
        ],
        'server' => [
            'label'   => 'Server and Rackmount Systems',
            'short'   => 'Server',
            'icon'    => 'server',
            'summary' => 'Home/small office and Enterprise Servers with efficient power draw for 24/7 running.',
            'points'  => [
                'Up to Dual CPU configurations for high computational loads.',
                'Up to 4 graphics cards for GPU/AI Compute workloads.',
                'High memory and storage with exceptional networking capabilities.',
            ],
            'focus'   => 'Drives and reliability come first. Power use and noise matter because it runs all day.',
            'profile' => [100, 80, 95, 90, 90],
            'rows'    => [
                ['Processor',     'Up to dual cpu configurations for high level compute scenarios',      'High'],
                ['Graphics', 'Up to 4 graphics cards for high level GPU or AI computing',            'High'],
                ['Network',    'Up to 40Gbps Ports for high level of data transfer',        'High'],
                ['Memory',     '64 GB and up with ECC for extra reliability',            'High'],
            ],
        ],
    ],

    /* ------------------------------------------------------------------
     * Gallery – these are SAMPLE entries drawn as illustrations.
     * To use a real photo, add 'image' => 'assets/img/your-photo.jpg'
     * (a 4:3 crop works best). Delete entries you don't need.
     * type must match a key in 'builds'.
     * ---------------------------------------------------------------- */
    'gallery' => [
        [
            'title' => 'Asus Matrix 5090 Gaming PC',
            'type'  => 'gaming',
            'spec'  => '16-core cpu, 64GB DDR5, RTX 5090, 4 TB NVMe, Wi-Fi 7',
            'desc'  => 'The most extreme computer ever built with the Asus Matrix RTX 5090 graphice card for the most overkill gaming experience.',
            'variant' => 0,
            'image' => 'matrix-5090',
            'alt'   => 'Asus Matrix RTX 5090 custom gaming PC built by Incrymnia',
        ],
        [
            'title' => 'Crimson Workstation',
            'type'  => 'workstation',
            'spec'  => '24-core CPU, 64 GB DDR4, 16 GB RX 9070 XT, 4 TB NVMe + 4 TB HDD storage',
            'desc'  => 'Extreme Threadripper 3960X Workstation build for heavy loads in development and 3D rendering.',
            'variant' => 0,
            'image' => 'crimson-workstation',
            'alt'   => 'Crimson Threadripper 3960X workstation built by Incrymnia',
        ],
        [
            'title' => 'The Incriminator',
            'type'  => 'server',
            'spec'  => '32-core Epyc Server CPU, 512 GB DDR4 ECC, 16 GB RX 9070 XT, 8 x 10TB SAS HDD, Quad 10 Gb NIC',
            'desc'  => 'An enterprise storage and virtualisation server featuring an AMD EPYC 7532 Server Processor with half a terrabyte of ECC memory for extreme workloads and 24/7 deployment.',
            'variant' => 0,
            'image' => 'the-incryminator-server',
            'alt'   => 'The Incriminator AMD EPYC rackmount server built by Incrymnia',
        ],
    ],

    /* ------------------------------------------------------------------
     * Services
     * ---------------------------------------------------------------- */
    'services' => [
        ['icon' => 'wrench',  'title' => 'Upgrades and repairs',      'text' => 'Computer feel a bit slow? Want a new upgrade? We can do that for you!'],
        ['icon' => 'swap',    'title' => 'Data migration and setup',  'text' => 'We move your files from the old PC and install Windows or Linux, drivers and your everyday apps.'],
        ['icon' => 'server',  'title' => 'Servers and Rackmounts',      'text' => 'Servers built and tested for long-term deployment reliability.'],
        ['icon' => 'box',     'title' => 'Bring your own parts',      'text' => 'Already own some components? We check compatibility and build around them.'],
        ['icon' => 'users',   'title' => 'Business and school orders', 'text' => 'Matching desktops with imaging, labels and a single point of contact.'],
        ['icon' => 'shield',  'title' => 'Warranty and support',      'text' => '{warranty}-month warranty on builds, plus help by phone or email after handover.'],
    ],

    /* ------------------------------------------------------------------
     * Process
     * ---------------------------------------------------------------- */
    'process' => [
        ['title' => 'Quote', 'text' => 'Get in contact with us today for an extensive evaluation of your needs and budget, to quote you the best system possible.', 'tag' => 'Free Quote'],
        ['title' => 'Order',   'text' => 'Once you place your order, we order the components from our suppliers to get your build started.', 'tag' => 'Parts ordered from Suppliers'],
        ['title' => 'Build and Test',     'text' => 'Once all the components are ready, within {days} days, your computer will be professionally assembled and tested to ensure smooth operation.', 'tag' => 'The building commences'],
        ['title' => 'Gaming begins!',    'text' => 'Or workstation and server productivity. And yes we all know that your office/study machine is for "studying" ;)', 'tag' => 'Pickup or Delivery'],
    ],

    /* ------------------------------------------------------------------
     * About
     * ---------------------------------------------------------------- */
    'standards' => [
        ['title' => 'Honest recommendations', 'text' => 'We will never upsell you on computers. We only recommend what is the best fit for your circumstances.'],
        ['title' => 'Standard parts',         'text' => 'Unlike other system builders, we use standard, non proprietary components, so in a case of an upgrade, these components are not locked or bound to a specific computer.'],
        ['title' => 'Tested before delivery', 'text' => 'All of our systems are stress tested intensively to ensure smooth operation and reliability before delivery.'],
        ['title' => 'Support after handover', 'text' => 'Questions, upgrades and warranty claims go to the people who built it.'],
    ],
    'brands' => ['Intel', 'AMD', 'NVIDIA', 'ASUS', 'MSI', 'Gigabyte', 'Corsair', 'Noctua', 'Samsung', 'Seagate', 'Western Digital', 'Crucial', 'Supermicro', 'Kingston'],

    /* ------------------------------------------------------------------
     * FAQ  ({warranty} and {days} are replaced from 'site' above)
     * ---------------------------------------------------------------- */
    'faq' => [
        ['q' => 'How long does a build take?',
         'a' => 'Most systems are ready in {days} working days once all parts are in. If something is out of stock, we discuss with you other possibilities to save you from waiting.'],
        ['q' => 'How much should I budget?',
         'a' => 'It depends on what you do with the machine. Tell us your budget and your use, and we’ll show you where the money is best spent. If your budget is too tight for what you want, we’ll say so and suggest what to change.'],
        ['q' => 'Can I supply my own parts?',
         'a' => 'Yes. We can accommodate for this but we also need to check that everything works together first. Parts you supply are covered by their own manufacturer warranty, and we warranty our workmanship.'],
        ['q' => 'What warranty do I get?',
         'a' => 'Every build has a {warranty}-month warranty covering parts and labour, and individual components keep their manufacturer warranty. We will confirm the exact terms in your quote.'],
        ['q' => 'Do you install Windows, Linux or other software?',
         'a' => 'Yes we can install Windows, Linux or any other softwares that you require, but by default, our computers do not include an operating system.'],
        ['q' => 'What sort of servers and workstations do you build?',
         'a' => 'RAID keeps a server running if a drive fails. It does not protect you from deletion, ransomware or fire. We recommend a second copy of important data, ideally off-site, and we can set that up for you.'],
        ['q' => 'Do you also build laptops as well as desktop computers?',
         'a' => 'Yes we do build laptops, but we usually upgrade the memory and storage from a stock laptop configuration.'],
        ['q' => 'Do you deliver?',
         'a' => 'We offer local pickup and insured shipping. Systems are packed with the cooler and graphics card secured so they arrive ready to power on.'],
    ],

    /* ------------------------------------------------------------------
     * Quote form options
     * ---------------------------------------------------------------- */
    'quote' => [
        'budgets' => [
            'under-1000' => 'Under $1,000',
            '1000-2000'  => '$1,000 to $2,000',
            '2000-4000'  => '$2,000 to $4,000',
            '4000-plus'  => '$4,000 or more',
            'unsure'     => 'Not sure yet',
        ],
        'timelines' => [
            'asap'     => 'As soon as possible',
            'month'    => 'Within a month',
            'flexible' => 'No rush',
        ],
        'extras' => [
            'os'        => 'Windows or Linux installed',
            'wifi'      => 'Wi-Fi and Bluetooth',
            'migration' => 'Move data from my old PC',
            'peripherals' => 'Monitor, keyboard and mouse',
            'own-parts' => 'I have parts to reuse',
            'delivery'  => 'Delivery or shipping',
        ],
    ],
];

/* Defaults for the SEO settings, page text and service landing pages (see includes/defaults.php). */
$defaults = require __DIR__ . '/includes/defaults.php';
$config['site'] += $defaults['site_extra'];
$config['seo']   = $defaults['seo'];
$config['text']  = $defaults['text'];
$config['pages'] = $defaults['pages'];
foreach ($defaults['build_pages'] as $buildKey => $landing) {
    if (isset($config['builds'][$buildKey])) {
        $config['builds'][$buildKey] += $landing;
    }
}

/* Private overrides (SMTP login etc.) live in config.local.php, which is never committed or served. */
$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

return $config;
