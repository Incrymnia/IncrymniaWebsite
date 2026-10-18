<?php
/**
 * Default content for the SEO settings, page text and the service landing pages.
 * Everything here can be edited from the admin panel; saved edits override these values.
 * {warranty} and {days} are replaced with the values from Business details.
 */

declare(strict_types=1);

return [

    'site_extra' => [
        'url'             => 'https://incrymnia.com',     // used for canonical links, sitemap and social previews
        'locale'          => 'en-AU',
        'alternate_names' => ['Incrymnia Computers', 'Incrymnia PC Builders'],
        'service_areas'   => ['Melbourne', 'Central Melbourne', 'South East Melbourne'],
        'social'          => ['facebook' => '', 'instagram' => '', 'youtube' => '', 'tiktok' => '', 'x' => '', 'linkedin' => ''],
        'schedule'        => [
            'mon' => ['09:00', '17:30'], 'tue' => ['09:00', '17:30'], 'wed' => ['09:00', '17:30'],
            'thu' => ['09:00', '17:30'], 'fri' => ['09:00', '17:30'],
            'sat' => ['10:00', '14:00'], 'sun' => null,
        ],
    ],

    'seo' => [
        'title'               => 'Incrymnia Computers | Custom PC Builder in Melbourne',
        'description'         => 'Incrymnia builds custom gaming PCs, workstations, study computers and servers in Melbourne. Hand-built, stress-tested and backed by an {warranty}-month warranty. Get a free quote.',
        'og_image'            => '',
        'google_verification' => '',
        'bing_verification'   => '',
        'analytics_id'        => '',
        'noindex'             => false,
        'redirect_to_canonical' => false,
    ],

    'text' => [
        'hero_title'      => 'Custom PCs, Workstations and Servers in Melbourne',
        'hero_lead'       => 'Incrymnia builds gaming rigs, study machines, workstations and servers for personal use and small offices across Melbourne. Every system is designed from the ground up and professionally assembled.',
        'hero_ticks'      => ['Free consultation', 'Itemised written quote', 'Tested before delivery'],
        'builds_title'    => 'What we build',
        'builds_intro'    => 'Four kinds of machines, each specified around how you will actually use it.',
        'services_title'  => 'More than just Desktop and Server Systems',
        'services_intro'  => 'Looking for a new computer but need something portable? We can also build your perfect Laptop.',
        'promo_title'     => 'Not sure what you need?',
        'promo_text'      => 'Book a free consultation. We will talk through your budget, your software and your space, then recommend a build.',
        'process_title'   => 'Our Process',
        'process_intro'   => 'From idea to execution, the four step process.',
        'gallery_title'   => 'Recent builds',
        'gallery_intro'   => 'A sneak peak behind closed doors of the systems we regularly assemble',
        'about_title'     => 'A small workshop that builds computers the way they should be',
        'about_paragraphs' => [
            'We build Gaming PCs, study and office machines, workstations and storage servers for people who want a system that fits how they use it, not whatever happens to be on a shelf.',
            'The heart of every computer starts with a requirement, whether you plan to game, study or require a powerhouse of a system, each computer is specifically designed with your intentions and requirements in mind.',
            'Every system isn\'t just built, its designed from the ground up, using the best possible components for your needs, and rigorously tested, to ensure the smoothest and most reliable operation possible.',
        ],
        'brands_title'    => 'Parts we commonly build with',
        'standards_title' => 'How we work',
        'faq_title'       => 'Questions we get asked',
        'faq_intro'       => 'Can’t find your answer? Send the quote form or email',
        'quote_title'     => 'Get a quote',
        'cta_title'       => 'Ready for a computer built around you?',
        'cta_text'        => 'Tell us what you need and we will reply with an itemised quote and a recommendation, free and with no obligation.',
        'quote_intro'     => 'Tell us what you need. We reply within {reply} with an itemised quote and a recommendation.',
    ],

    /* The other pages of the site: /gallery/, /services/, /about/, /faq/, /contact/ */
    'pages' => [
        'gallery' => [
            'label'       => 'Gallery',
            'title'       => 'Custom PC Build Gallery in Melbourne | Incrymnia',
            'description' => 'Photos and full specs of custom gaming PCs, workstations, office computers and servers built by Incrymnia in Melbourne.',
            'h1'          => 'Gallery of custom PC builds',
            'intro'       => 'Photos and specs of systems we have designed and built. Filter by type, or open any build for the full details.',
        ],
        'services' => [
            'label'       => 'Services',
            'title'       => 'Computer Building Services in Melbourne | Incrymnia',
            'description' => 'Custom computer building, upgrades, repairs, data migration and server setup in Melbourne. See how an Incrymnia build works from quote to delivery.',
            'h1'          => 'Computer building services in Melbourne',
            'intro'       => 'Everything we offer beyond a standard build, and how a project works from your first message to handover.',
        ],
        'about' => [
            'label'       => 'About us',
            'title'       => 'About Incrymnia | Custom PC Builders in Melbourne',
            'description' => 'Meet Incrymnia, a small Melbourne workshop designing and building gaming PCs, workstations, office computers and servers to order.',
            'h1'          => 'About Incrymnia',
            'intro'       => 'A small workshop that designs every computer around what the person using it actually needs.',
        ],
        'faq' => [
            'label'       => 'FAQ',
            'title'       => 'Custom PC Building FAQ | Incrymnia Melbourne',
            'description' => 'Answers to common questions about custom PC builds, warranty, build times, parts, upgrades and servers from Incrymnia in Melbourne.',
            'h1'          => 'Frequently asked questions',
            'intro'       => 'Quick answers about custom builds, warranty and how we work.',
        ],
        'contact' => [
            'label'       => 'Contact',
            'title'       => 'Get a Free Quote | Contact Incrymnia Melbourne',
            'description' => 'Request a free quote for a custom gaming PC, workstation, office computer or server from Incrymnia in Melbourne.',
            'h1'          => 'Contact us and get a free quote',
            'intro'       => 'Tell us what you need and we will reply with an itemised quote and a recommendation.',
        ],
    ],

    /* Landing pages: one per build type. Slug becomes the URL, e.g. /gaming-pcs/ */
    'build_pages' => [

        'gaming' => [
            'slug' => 'gaming-pcs',
            'page' => [
                'seo_title'       => 'Custom Gaming PC Builder in Melbourne | Incrymnia',
                'seo_description' => 'Custom gaming PCs built to order in Melbourne. Parts matched to your monitor and games, stress-tested and warrantied for {warranty} months. Get a free quote.',
                'h1'              => 'Custom gaming PCs built in Melbourne',
                'intro'           => [
                    'Incrymnia builds custom gaming computers for players across Central and South East Melbourne. Every gaming PC is designed around the games you play, the monitor you play on and the budget you set, then assembled by hand and tested before it reaches you.',
                    'Pre-built gaming PCs from big retailers often spend money in the wrong places: a fast graphics card paired with a weak power supply, or flashy lighting while storage and cooling are an afterthought. We spec the whole system so the money goes where it improves your frame rates.',
                ],
                'sections' => [
                    ['heading' => 'Matched to your screen and games',
                     'text'    => 'Frame rates come mostly from the graphics card, so we start there. A 1080p high refresh rate monitor, a 1440p display and a 4K screen each need very different hardware. Tell us the resolution, refresh rate and the titles you care about, and we will recommend a graphics card and processor that balance each other instead of one holding the other back.'],
                    ['heading' => 'Cooling, noise and lighting',
                     'text'    => 'A gaming PC that throttles or sounds like a jet engine is no fun. We choose cases for airflow, tune fan curves for quiet running under load, and add lighting only if you want it. Black, white or full RGB: your call.'],
                    ['heading' => 'Built with standard parts',
                     'text'    => 'We use standard, non-proprietary components, so you can upgrade the graphics card, add memory or swap storage later without being locked to one brand\'s custom motherboard or power supply.'],
                    ['heading' => 'Tested before delivery',
                     'text'    => 'Every system is stress tested before pickup or delivery and covered by a {warranty}-month warranty on parts and labour. If something is not right, we fix it.'],
                ],
                'faq' => [
                    ['q' => 'How much does a custom gaming PC cost?',
                     'a' => 'It depends on the resolution and games you are aiming for. Tell us your budget and we will show you where the money is best spent. If your budget is too tight for what you want, we will say so and suggest what to change.'],
                    ['q' => 'Can you build around parts I already own?',
                     'a' => 'Yes. We check compatibility first, then build around parts you want to keep, such as a graphics card, case or storage. Parts you supply keep their own manufacturer warranty.'],
                    ['q' => 'How long does a custom gaming PC take to build?',
                     'a' => 'Most gaming PCs are ready within {days} once all the parts have arrived.'],
                ],
            ],
        ],

        'study' => [
            'slug' => 'office-study-pcs',
            'page' => [
                'seo_title'       => 'Office and Study Computers in Melbourne | Incrymnia',
                'seo_description' => 'Reliable, quiet office and study computers built to order in Melbourne. Fast SSD, plenty of memory and set up for work, uni and school.',
                'h1'              => 'Office and study computers built in Melbourne',
                'intro'           => [
                    'A good office or study computer should start quickly, run quietly and keep working for years. Incrymnia builds office, home office, school and uni PCs for businesses, students and families around Melbourne, with the right balance of speed and price instead of paying for power you will never use.',
                ],
                'sections' => [
                    ['heading' => 'Fast where it matters',
                     'text'    => 'A fast SSD makes the biggest difference to how a computer feels, so every office and study PC gets one. We add enough memory to keep plenty of browser tabs, documents and video calls open together.'],
                    ['heading' => 'Quiet and reliable',
                     'text'    => 'Quiet fans mean you can use the computer in an open-plan office, a bedroom or a shared space. We use trusted components and test every system before it is handed over.'],
                    ['heading' => 'Ready to use',
                     'text'    => 'We can install Windows or Linux, drivers and the programs you need, set up Wi-Fi and configure parental controls, so the computer is ready for work or homework on day one.'],
                    ['heading' => 'Orders for teams and families',
                     'text'    => 'Need several matching computers for a small business or a household? We can build them as a batch with a single point of contact, and because we use standard parts, any of them can be upgraded later.'],
                ],
                'faq' => [
                    ['q' => 'What do I need for office work, university or school?',
                     'a' => 'For most office and coursework a modern processor, 16 GB of memory and a fast SSD is plenty. If you work in design, engineering or video, tell us the software and we will size the system for it.'],
                    ['q' => 'Do office and study computers come with Windows?',
                     'a' => 'By default our computers do not include an operating system. We can install Windows, Linux or any other software you need.'],
                    ['q' => 'Can you supply several computers for a business or school?',
                     'a' => 'Yes. We build matching desktops in batches, with imaging and a single point of contact. Tell us how many you need in the quote form.'],
                ],
            ],
        ],

        'workstation' => [
            'slug' => 'workstations',
            'page' => [
                'seo_title'       => 'Custom Workstation Builds in Melbourne | Incrymnia',
                'seo_description' => 'Custom workstations for video editing, 3D rendering, CAD and development, built to order in Melbourne. High core-count CPUs, ECC options and fast storage.',
                'h1'              => 'Custom workstations built in Melbourne',
                'intro'           => [
                    'Incrymnia builds workstations for video editors, 3D artists, engineers, developers and data professionals. We start with the software you run and the deadlines you work to, then choose a processor, memory, graphics and storage combination that finishes your renders, compiles and exports sooner.',
                ],
                'sections' => [
                    ['heading' => 'Built around your software',
                     'text'    => 'Different applications stress different parts. Rendering and compiling love high core counts. Large datasets and timelines need lots of memory. GPU renderers and AI tools need graphics memory. We spec the system for your workload, not for a generic benchmark.'],
                    ['heading' => 'Memory and storage that keep up',
                     'text'    => 'We commonly build workstations with 64 GB or more of memory, using ECC memory where reliability matters, plus fast NVMe scratch drives alongside bulk storage for projects and archives.'],
                    ['heading' => 'Cooling for sustained loads',
                     'text'    => 'Workstations run flat out for hours. We choose cooling and cases that hold clock speeds under long loads while keeping noise reasonable.'],
                    ['heading' => 'Tested and supported',
                     'text'    => 'Each workstation is stress tested before delivery and covered by a {warranty}-month warranty, with support from the people who built it.'],
                ],
                'faq' => [
                    ['q' => 'What is the difference between a workstation and a gaming PC?',
                     'a' => 'They share many parts, but a workstation prioritises sustained performance, memory capacity and reliability over frame rates. We choose the processor, memory and graphics card for your professional software rather than for games.'],
                    ['q' => 'Can you build a workstation for video editing, 3D rendering or CAD?',
                     'a' => 'Yes. Tell us the software and the size of your projects and we will recommend a configuration. We regularly build for video editing, 3D rendering, CAD and development work.'],
                ],
            ],
        ],

        'server' => [
            'slug' => 'servers',
            'page' => [
                'seo_title'       => 'Custom Server and Rackmount Builds in Melbourne | Incrymnia',
                'seo_description' => 'Custom servers, rackmount systems and storage servers built in Melbourne. Dual-CPU, GPU compute, ECC memory and fast networking, tested for 24/7 running.',
                'h1'              => 'Custom servers and rackmount systems built in Melbourne',
                'intro'           => [
                    'Incrymnia designs and builds servers for homes, small offices and enterprise workloads: file and backup servers, virtualisation hosts, GPU and AI compute machines and rackmount systems. Each server is specified for reliability and efficient power draw, then tested for long-term, 24/7 deployment.',
                ],
                'sections' => [
                    ['heading' => 'Compute, memory and GPUs',
                     'text'    => 'We build systems with up to dual CPU configurations for heavy computational loads, large pools of ECC memory, and up to four graphics cards for GPU and AI compute workloads.'],
                    ['heading' => 'Storage and networking',
                     'text'    => 'Storage servers use the right drives and layout for your data and your recovery needs, with networking up to 40 Gbps for fast transfers between machines.'],
                    ['heading' => 'Reliability and backups',
                     'text'    => 'RAID keeps a server running if a drive fails, but it does not protect against deletion, ransomware or fire. We recommend a second copy of important data, ideally off-site, and can help set that up.'],
                    ['heading' => 'Built for 24/7 running',
                     'text'    => 'Servers are built with efficient components, suitable cooling and standard parts that are easy to maintain, and every system is stress tested before delivery.'],
                ],
                'faq' => [
                    ['q' => 'Do you build rackmount servers?',
                     'a' => 'Yes. We build rackmount and tower servers, including dual-processor systems, GPU compute servers and storage servers.'],
                    ['q' => 'Can you build a NAS or backup server?',
                     'a' => 'Yes. We build file, backup and storage servers for homes and small offices, with drive layouts and networking to suit how you work.'],
                ],
            ],
        ],
    ],
];
