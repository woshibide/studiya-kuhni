<?php

namespace JR;

use Kirby\Cms\App as Kirby;

require_once __DIR__ . '/media.class.php';
require_once __DIR__ . '/class.php';

Kirby::plugin('jr/static-site-generator', [
  'api' => [
    'routes' => function ($kirby) {
        $endpoint = $kirby->option('jr.static_site_generator.endpoint');
        if (!$endpoint) {
            return [];
        }

        return [
          [
            'pattern' => $endpoint,
            'action' => function () use ($kirby) {
                if (function_exists('set_time_limit')) {
                    @set_time_limit(0);
                }

                @ini_set('max_execution_time', '0');

                $list = StaticSiteGenerator::generateFromConfig($kirby);
                $count = count($list);
                return ['success' => true, 'files' => $list, 'message' => "$count files generated / copied"];
            },
            'method' => 'POST'
          ]
        ];
    }
  ],
  'fields' => [
    'staticSiteGenerator' => [
      'props' => [
        'endpoint' => function () {
          return $this->kirby()->option('jr.static_site_generator.endpoint');
        }
      ]
    ]
  ],
  'commands' => [
    'ssg:generate' => [
      'description' => 'Generate Static Site',
      'args' => [],
      'command' => function($cli) {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        @ini_set('max_execution_time', '0');

        $list = StaticSiteGenerator::generateFromConfig($cli->kirby());
        $count = count($list);

        $cli->success("Static site generated. $count files generated / copied");
      }
    ]
  ]
]);
