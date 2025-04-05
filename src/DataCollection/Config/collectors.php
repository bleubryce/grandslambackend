<?php

return [
    'mlb_stats' => [
        'enabled' => true,
        'base_url' => 'https://statsapi.mlb.com/api/v1',
        'rate_limit' => [
            'requests_per_minute' => 60,
            'pause_on_limit' => true
        ],
        'endpoints' => [
            'teams' => '/teams',
            'players' => '/players',
            'games' => '/schedule/games',
            'game_stats' => '/game/{gameId}/boxscore',
            'player_stats' => '/people/{playerId}/stats'
        ],
        'seasons' => [
            'start_year' => 2023,
            'end_year' => 2024
        ],
        'cache' => [
            'enabled' => true,
            'ttl' => 3600 // 1 hour
        ]
    ],
    'baseball_reference' => [
        'enabled' => true,
        'base_url' => 'https://www.baseball-reference.com',
        'rate_limit' => [
            'requests_per_minute' => 20,
            'pause_on_limit' => true
        ],
        'endpoints' => [
            'teams' => '/teams/',
            'players' => '/players/',
            'leagues' => '/leagues/'
        ],
        'scraping_rules' => [
            'respect_robots_txt' => true,
            'user_agent' => 'BaseballAnalytics/1.0',
            'delay_between_requests' => 3 // seconds
        ],
        'cache' => [
            'enabled' => true,
            'ttl' => 86400 // 24 hours
        ]
    ],
    'fangraphs' => [
        'enabled' => true,
        'base_url' => 'https://www.fangraphs.com',
        'rate_limit' => [
            'requests_per_minute' => 30,
            'pause_on_limit' => true
        ],
        'endpoints' => [
            'leaders' => '/leaders.aspx',
            'players' => '/players.aspx',
            'teams' => '/teams.aspx'
        ],
        'scraping_rules' => [
            'respect_robots_txt' => true,
            'user_agent' => 'BaseballAnalytics/1.0',
            'delay_between_requests' => 2 // seconds
        ],
        'cache' => [
            'enabled' => true,
            'ttl' => 43200 // 12 hours
        ]
    ]
]; 