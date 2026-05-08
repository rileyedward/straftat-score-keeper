# Straftat Score Keeper

Companion web app for tracking score across game-night tournaments of [Straftat](https://store.steampowered.com/app/2386720/STRAFTAT/), the fast-paced first-person arena shooter.

## Overview

### What is Straftat Score Keeper?

Straftat Score Keeper fills the gap left by the base game's lack of a built-in tournament mode. Designed for casual friend-group sessions, it lets a host log matches across **1v1**, **2v2**, and **FFA** modes throughout the night, computes a unified leaderboard that fairly weighs all three modes, and crowns a champion (with a few bonus stars) when the night wraps up. Players persist across sessions so stats build up over time.

There are no real user accounts — a single shared passcode (set in `.env`) gates the whole app, so the host and their friends can all add matches and edit players freely.

### Key Features

- **Tournament Sessions**: One active "tournament" at a time represents a game night. End it to lock results and start a new one.
- **Smart Match Wizard**: Three-step flow (mode → players → score). FFA placements derive automatically from rounds-won, with proper tie handling.
- **Combined Leaderboard**: Mode-aware scoring — 1v1 win = 10 MP, 2v2 win = 13 MP/teammate, FFA placements = 20/15/10/5 (4p) or 15/10/5 (3p), plus 1 RP per round won. Filterable by overall, 1v1, 2v2, or FFA.
- **Bonus Stars (Achievements)**: 8 Mario-Party-style awards computed at end-of-tournament — MVP, The Sniper, Bracket Buster, Wingman, Lone Wolf, Iron Lung, Cellar Dweller, Comeback Kid.
- **Player Roster**: Players persist across tournaments. Smart autocomplete lets you create new players inline. Each player has a profile page with image upload and cross-tournament stats.
- **History**: Every ended tournament archived with full leaderboard and achievements.
- **Soviet Brutalist UI**: Dark theme echoing Straftat's grimy, retro arena aesthetic — Oswald + JetBrains Mono, hard 2px borders, signal-red accents, CRT scanline overlay.

## Getting Started

### Prerequisites

Ensure you have the following installed:

1. **PHP** (8.2+) is required for the application:

   ```bash
   php --version
   ```

2. **Composer** for managing PHP dependencies:

   ```bash
   composer --version
   ```

3. **Node** and **NPM** for managing frontend dependencies:

   ```bash
   node --version
   npm --version
   ```

> SQLite is used by default for local development — no separate database server needed. PostgreSQL is used in production (e.g. Laravel Cloud).

### Installation

1. Duplicate the example environment file and configure it with your settings:

   ```bash
   cp .env.example .env
   ```

2. Set the shared passcode in `.env`:

   ```
   STRAFTAT_PASSCODE=your-passcode-here
   ```

3. Install PHP and JavaScript dependencies:

   ```bash
   composer install
   npm install
   ```

4. Generate a new application key:

   ```bash
   php artisan key:generate
   ```

5. Create the SQLite database file and run migrations:

   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

6. (Optional) Seed the database with a demo tournament for local poking:

   ```bash
   php artisan db:seed
   ```

7. Symlink the public storage disk so uploaded player images are servable:

   ```bash
   php artisan storage:link
   ```

8. Run the dev stack (Laravel server + Vite + queue worker concurrently):

   ```bash
   composer run dev
   ```

   Then open [http://localhost:8000](http://localhost:8000) and enter your passcode.

## Tech Stack

- **Backend**: Laravel 13, PHP 8.4, SQLite (local) / PostgreSQL (production)
- **Frontend**: Inertia.js 2, Vue 3 (Composition API + TypeScript), Tailwind CSS 4, shadcn-vue
- **Auth**: Custom passcode middleware (no real users)
- **Storage**: Storage facade — `public` disk locally, `s3` (Laravel Cloud) in production via env swap
- **Testing**: Pest

## Project Structure

```
app/
├── Actions/                    # Pure-ish business logic
│   ├── ComputeTournamentLeaderboard.php
│   ├── ComputeAchievements.php
│   ├── FinalizeTournament.php
│   ├── RecordMatch.php         # Validates + auto-derives FFA placements
│   └── UpsertPlayer.php
├── Enums/
│   ├── MatchMode.php           # 1v1 | 2v2 | ffa
│   └── TournamentStatus.php    # active | ended
├── Http/
│   ├── Controllers/            # Auth, Home, Tournament, Match, Player
│   └── Middleware/
│       └── RequirePasscode.php
└── Models/
    ├── Player.php
    ├── Tournament.php
    ├── GameMatch.php           # Renamed to avoid PHP `match` keyword
    └── MatchParticipant.php

resources/js/
├── components/                 # AddMatchWizard, LeaderboardCard, PlayerAutocomplete, etc.
├── layouts/AppShell.vue
├── pages/                      # Auth, Home, Tournaments/*, Players/*
└── types/index.ts

database/
├── migrations/                 # 5 domain tables
├── factories/
└── seeders/DatabaseSeeder.php
```

## Domain Model

- **Tournament** — the per-night container. Only one `active` tournament at a time (enforced by a partial unique index).
- **Player** — global, persists across tournaments. Created on-the-fly via the autocomplete in the create-tournament flow.
- **GameMatch** — a single Straftat match (first to 4 rounds). Mode-aware: 2v2 stores team rounds at the match level (`team_a_rounds`, `team_b_rounds`); 1v1 and FFA store rounds per participant.
- **MatchParticipant** — one row per player per match. For FFA, `placement` is derived server-side from `rounds_won` using standard competition ranking (ties share the higher place, the next slot is skipped).

## Scoring

| Mode | Match Points | Round Points |
|------|--------------|--------------|
| 1v1  | Winner: **10**, Loser: **0** | 1 RP per round won |
| 2v2  | Each winning teammate: **13**, Each losing teammate: **0** | 1 RP per round the team won |
| FFA-4p | Place 1/2/3/4: **20 / 15 / 10 / 5** | 1 RP per round won |
| FFA-3p | Place 1/2/3: **15 / 10 / 5** | 1 RP per round won |

**Total session score = MP + RP.** Computed on the fly, no cached column.

Tied FFA placements share their (higher) place's MP — e.g. two players tied for 2nd in a 4-player FFA both get 15 MP.

## Testing

```bash
php artisan test
```

Pest test suite covers:

- Passcode middleware redirect / login / logout
- `RecordMatch` validation per mode (rejects malformed payloads)
- FFA placement derivation (no ties / tied middle places)
- `ComputeTournamentLeaderboard` math per mode, including FFA tie scoring

## Deployment

The app targets **Laravel Cloud** for production:

1. Set `FILESYSTEM_DISK=s3` and the standard `AWS_*` env vars on the deploy target. The `Player::image_url` accessor uses `Storage::disk(config('filesystems.default'))` so no code change is needed.
2. Set `DB_CONNECTION=pgsql` and Laravel Cloud's database env vars.
3. Set a strong `STRAFTAT_PASSCODE`.
