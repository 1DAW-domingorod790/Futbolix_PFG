<?php

return [
    'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
    'temperature' => (float) env('GROQ_TEMPERATURE', 0.4),
    'max_completion_tokens' => (int) env('GROQ_MAX_COMPLETION_TOKENS', 900),
    'reasoning_effort' => env('GROQ_REASONING_EFFORT', 'low'),
    'timeout_seconds' => (int) env('GROQ_TIMEOUT_SECONDS', 30),
    'include_conversation_history' => (bool) env('FUTBOLIX_AI_INCLUDE_CONVERSATION_HISTORY', false),
    'max_context_messages' => (int) env('FUTBOLIX_AI_MAX_CONTEXT_MESSAGES', 2),
    'max_message_context_chars' => (int) env('FUTBOLIX_AI_MAX_MESSAGE_CONTEXT_CHARS', 800),
    'max_system_context_chars' => (int) env('FUTBOLIX_AI_MAX_SYSTEM_CONTEXT_CHARS', 30000),
    'max_competitions_context' => (int) env('FUTBOLIX_AI_MAX_COMPETITIONS_CONTEXT', 5),
    'max_standings_context' => (int) env('FUTBOLIX_AI_MAX_STANDINGS_CONTEXT', 25),
    'max_competition_games_context' => (int) env('FUTBOLIX_AI_MAX_COMPETITION_GAMES_CONTEXT', 30),
    'max_teams_context' => (int) env('FUTBOLIX_AI_MAX_TEAMS_CONTEXT', 8),
    'max_team_games_context' => (int) env('FUTBOLIX_AI_MAX_TEAM_GAMES_CONTEXT', 80),
    'estimated_tokens_per_message' => (int) env('FUTBOLIX_AI_ESTIMATED_TOKENS_PER_MESSAGE', 600),
    'tokens_per_credit' => (int) env('FUTBOLIX_AI_TOKENS_PER_CREDIT', 750),
    'minimum_credits_per_message' => (int) env('FUTBOLIX_AI_MINIMUM_CREDITS_PER_MESSAGE', 1),
    'maximum_credits_per_message' => (int) env('FUTBOLIX_AI_MAXIMUM_CREDITS_PER_MESSAGE', 5),
    'web_search_enabled' => (bool) env('FUTBOLIX_AI_WEB_SEARCH_ENABLED', false),
    'web_search_tool' => env('FUTBOLIX_AI_WEB_SEARCH_TOOL', 'browser_search'),
    'web_search_tool_choice' => env('FUTBOLIX_AI_WEB_SEARCH_TOOL_CHOICE', 'auto'),

    'plans' => [
        'free' => [
            'monthly_credit_limit' => (int) env('FUTBOLIX_AI_FREE_CREDITS', 50),
        ],
        'pro' => [
            'monthly_credit_limit' => (int) env('FUTBOLIX_AI_PRO_CREDITS', 500),
        ],
    ],
];
