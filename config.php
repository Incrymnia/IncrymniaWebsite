<?php
/**
 * Emberforge – site configuration and content.
 *
 * Everything you are likely to edit lives here: business details, build types,
 * gallery entries, services, FAQ and the quote-form options.
 * Anything marked REPLACE is placeholder text.
 */

declare(strict_types=1);

return [

    /* ------------------------------------------------------------------
     * Business details
     * ---------------------------------------------------------------- */
    'site' => [
        'name'        => 'Emberforge',                       // REPLACE with your business name
        'tagline'     => 'Custom PCs and storage servers, built by hand.',
        'description' => 'Custom-built gaming PCs, study computers, workstations and NAS or storage servers. Assembled, cable-managed and stress-tested before delivery. Request a free quote.',

        'email'       => 'hello@example.com',                // REPLACE – shown on the site
        'quote_to'    => 'hello@example.com',                // REPLACE – where quote requests are emailed
        'mail_from'   => 'no-reply@example.com',             // REPLACE – should be an address on your own domain
        'phone'       => '[Phone number]',                   // REPLACE
        'phone_href'  => '',                                 // REPLACE e.g. '+61400000000' (leave empty to hide the link)
        'address'     => '[Street address, Suburb]',         // REPLACE
        'hours'       => [
            ['Monday to Friday', '9:00 am to 5:30 pm'],
            ['Saturday',         '10:00 am to 2:00 pm'],
            ['Sunday',           'Closed'],
        ],

        'reply_time'     => 'one business day',              // shown after a quote request is sent
        'warranty_years' => 3,                               // REPLACE with your real warranty
        'build_days'     => '5 to 7',                        // REPLACE with your real typical build time
    ],

    /* ------------------------------------------------------------------
     * Hero stats (REPLACE with real numbers, or delete the ones you don't want)
     * ---------------------------------------------------------------- */
    'stats' => [
        ['value' => 350, 'suffix' => '+',     'label' => 'Systems built',          'note' => 'Gaming, study, workstation and storage'],
        ['value' => 3,   'suffix' => ' yrs',  'label' => 'Warranty on every build', 'note' => 'Parts and labour'],
        ['value' => 7,   'suffix' => ' days', 'label' => 'Typical build time',      'note' => 'From the day parts arrive'],
        ['value' => 24,  'suffix' => ' hrs',  'label' => 'Stress testing',          'note' => 'Before anything ships'],
    ],

    /* ------------------------------------------------------------------
     * Build types
     * profile: relative weighting (0-100) for CPU, GPU, Memory, Storage, Quiet
     * rows:    [component, note, priority (High|Medium|Low)]
     * ---------------------------------------------------------------- */
    'profile_labels' => ['CPU', 'GPU', 'Memory', 'Storage', 'Quiet'],

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
                ['Memory',        '32 GB as a comfortable starting point',        'Medium'],
                ['Case and cooling', 'Airflow first, lighting if you want it',    'Low'],
            ],
        ],
        'study' => [
            'label'   => 'School and study PCs',
            'short'   => 'Study',
            'icon'    => 'cap',
            'summary' => 'Reliable, quiet computers for homework, uni, remote work and everyday use. Fast to start, easy on the power bill.',
            'points'  => [
                'Quick SSD so it boots in seconds',
                'Quiet enough for a bedroom or library desk',
                'Wi-Fi, Office and parental controls set up',
            ],
            'focus'   => 'Fast storage and quiet running matter more than raw power.',
            'profile' => [40, 15, 40, 55, 90],
            'rows'    => [
                ['Storage',   'A fast SSD makes everything feel quicker',     'High'],
                ['Noise',     'Quiet fans for shared rooms',                  'High'],
                ['Processor', 'Plenty for office, browsers and video calls',  'Medium'],
                ['Graphics',  'Built-in graphics are usually enough',         'Low'],
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
            'profile' => [95, 75, 95, 85, 60],
            'rows'    => [
                ['Processor', 'High core count for renders and compiles',    'High'],
                ['Memory',    '64 GB and up, ECC where it matters',          'High'],
                ['Graphics',  'Picked for your software, not for the box',   'Medium'],
                ['Storage',   'Fast scratch NVMe plus bulk drives',          'Medium'],
            ],
        ],
        'storage' => [
            'label'   => 'NAS and storage servers',
            'short'   => 'Storage',
            'icon'    => 'server',
            'summary' => 'Home and small-office file servers, backup targets and media servers. Reliable drives, sensible RAID and low power draw for 24/7 running.',
            'points'  => [
                'RAID, ZFS or Unraid, set up and documented',
                'NAS-rated drives and optional 2.5G or 10G networking',
                'Backups, remote access and user permissions',
            ],
            'focus'   => 'Drives and reliability come first. Power use and noise matter because it runs all day.',
            'profile' => [45, 5, 60, 100, 70],
            'rows'    => [
                ['Drives',     'NAS-rated disks in RAID, ZFS or Unraid',      'High'],
                ['Power draw', 'Efficient parts for 24/7 running',            'High'],
                ['Network',    '2.5G or 10G ports for fast transfers',        'Medium'],
                ['Memory',     'Sized to your pool, ECC optional',            'Medium'],
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
            'title' => 'Ember Tower',
            'type'  => 'gaming',
            'spec'  => '8-core CPU, 16 GB graphics card, 32 GB DDR5, 2 TB NVMe',
            'desc'  => 'A black and red build for 1440p high refresh rate gaming, with a tempered glass side panel and a tuned fan curve.',
            'variant' => 0,
            'image' => null,
        ],
        [
            'title' => 'Study Silent',
            'type'  => 'study',
            'spec'  => '6-core CPU, 16 GB DDR4, 1 TB NVMe, Wi-Fi 6',
            'desc'  => 'A small, near-silent tower for uni and home study. Quick to boot, sips power and fits under a desk.',
            'variant' => 1,
            'image' => null,
        ],
        [
            'title' => 'Render Box',
            'type'  => 'workstation',
            'spec'  => '16-core CPU, 64 GB DDR5, 12 GB GPU, 2 TB + 4 TB storage',
            'desc'  => 'Built for video editing and 3D rendering, with a dual-radiator cooling loop and separate scratch and archive drives.',
            'variant' => 2,
            'image' => null,
        ],
        [
            'title' => 'Home NAS, 4-bay',
            'type'  => 'storage',
            'spec'  => '4 x 8 TB NAS drives, RAID 5, 2.5G networking',
            'desc'  => 'A quiet file and backup server for a household. Photos, documents and media in one place with automatic backups.',
            'variant' => 0,
            'image' => null,
        ],
        [
            'title' => 'White Frost',
            'type'  => 'gaming',
            'spec'  => '12-core CPU, 20 GB graphics card, 32 GB DDR5, 2 TB NVMe',
            'desc'  => 'An all-white 4K build with matching braided cables and low-noise fans.',
            'variant' => 2,
            'image' => null,
        ],
        [
            'title' => 'Homework Hub',
            'type'  => 'study',
            'spec'  => '4-core CPU, 16 GB DDR4, 500 GB SSD, Wi-Fi',
            'desc'  => 'A budget-friendly build for school, delivered with Windows, Office and a parental-control profile ready to go.',
            'variant' => 3,
            'image' => null,
        ],
        [
            'title' => 'Dev and CAD Station',
            'type'  => 'workstation',
            'spec'  => '24-core CPU, 128 GB ECC memory, pro GPU, 4 TB NVMe',
            'desc'  => 'A workstation for CAD, simulation and large builds, with ECC memory and a tool-less case for easy upgrades.',
            'variant' => 1,
            'image' => null,
        ],
        [
            'title' => 'Backup and Media Server, 6-bay',
            'type'  => 'storage',
            'spec'  => '6 x 12 TB drives, ZFS, 10 GbE, UPS-ready',
            'desc'  => 'A small-office server for shared files, nightly backups and media streaming, with remote access and user permissions configured.',
            'variant' => 1,
            'image' => null,
        ],
    ],

    /* ------------------------------------------------------------------
     * Services
     * ---------------------------------------------------------------- */
    'services' => [
        ['icon' => 'wrench',  'title' => 'Upgrades and repairs',      'text' => 'Faster storage, more memory, a new graphics card. We fit it, test it and tell you what we found.'],
        ['icon' => 'swap',    'title' => 'Data migration and setup',  'text' => 'We move your files from the old PC and install Windows or Linux, drivers and your everyday apps.'],
        ['icon' => 'server',  'title' => 'Server and NAS setup',      'text' => 'RAID layout, network, backups, remote access and user permissions configured and written down.'],
        ['icon' => 'box',     'title' => 'Bring your own parts',      'text' => 'Already own some components? We check compatibility and build around them.'],
        ['icon' => 'users',   'title' => 'Business and school orders', 'text' => 'Matching desktops with imaging, labels and a single point of contact.'],
        ['icon' => 'shield',  'title' => 'Warranty and support',      'text' => '{warranty}-year warranty on builds, plus help by phone or email after handover.'],
    ],

    /* ------------------------------------------------------------------
     * Process
     * ---------------------------------------------------------------- */
    'process' => [
        ['title' => 'Tell us what you need', 'text' => 'Send the quote form or call. We ask what you run, your budget and where the computer will live.', 'tag' => 'Free'],
        ['title' => 'Get a written quote',   'text' => 'You receive an itemised parts list with the reasoning, and a cheaper or faster option if it helps.', 'tag' => 'No obligation'],
        ['title' => 'We build and test',     'text' => 'Assembly, cable management, BIOS and OS setup, then a {days} day build with a full stress test.', 'tag' => 'In the workshop'],
        ['title' => 'Pickup or delivery',    'text' => 'We walk you through the system, hand over the paperwork and stay on call for questions.', 'tag' => 'Warranty starts'],
    ],

    /* ------------------------------------------------------------------
     * About
     * ---------------------------------------------------------------- */
    'standards' => [
        ['title' => 'Honest recommendations', 'text' => 'We tell you when a cheaper part does the job and when it won’t.'],
        ['title' => 'Standard parts',         'text' => 'No proprietary boards or power supplies, so upgrades and repairs stay simple.'],
        ['title' => 'Tested before delivery', 'text' => 'Stress tests, temperature checks and a clean OS install before handover.'],
        ['title' => 'Support after handover', 'text' => 'Questions, upgrades and warranty claims go to the people who built it.'],
    ],
    'brands' => ['Intel', 'AMD', 'NVIDIA', 'ASUS', 'MSI', 'Gigabyte', 'Corsair', 'Noctua', 'Samsung', 'Seagate', 'Western Digital', 'Crucial'],

    /* ------------------------------------------------------------------
     * FAQ  ({warranty} and {days} are replaced from 'site' above)
     * ---------------------------------------------------------------- */
    'faq' => [
        ['q' => 'How long does a build take?',
         'a' => 'Most systems are ready in {days} working days once all parts are in. If something is out of stock, we tell you before we order and agree on a plan with you.'],
        ['q' => 'How much should I budget?',
         'a' => 'It depends on what you do with the machine. Tell us your budget and your use, and we’ll show you where the money is best spent. If your budget is too tight for what you want, we’ll say so and suggest what to change.'],
        ['q' => 'Can I supply my own parts?',
         'a' => 'Yes. We check that everything works together first. Parts you supply are covered by their own manufacturer warranty, and we warranty our workmanship.'],
        ['q' => 'What warranty do I get?',
         'a' => 'Every build has a {warranty}-year warranty covering parts and labour, and individual components keep their manufacturer warranty. We will confirm the exact terms in your quote.'],
        ['q' => 'Do you install Windows, Linux or other software?',
         'a' => 'Yes. We can install Windows or Linux, drivers and the apps you use, or leave the machine ready for you to set up. For servers we can install TrueNAS, Unraid, Proxmox or a plain Linux server.'],
        ['q' => 'What is the difference between RAID and a backup?',
         'a' => 'RAID keeps a server running if a drive fails. It does not protect you from deletion, ransomware or fire. We recommend a second copy of important data, ideally off-site, and we can set that up for you.'],
        ['q' => 'Can you upgrade or repair my current PC?',
         'a' => 'Usually, yes. Include the make and model (or a photo of the specs) in the quote form and we’ll tell you what’s worth upgrading and what isn’t.'],
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
