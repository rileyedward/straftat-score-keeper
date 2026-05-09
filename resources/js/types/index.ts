export * from './auth';

export type MatchMode = '1v1' | '2v2' | 'ffa';

export interface Player {
    id: number;
    name: string;
    image_url: string | null;
}

export interface MatchParticipant {
    id: number;
    player_id: number;
    player_name: string;
    team: 'A' | 'B' | null;
    placement: number | null;
    rounds_won: number;
}

export interface MatchRecord {
    id: number;
    mode: MatchMode;
    team_a_rounds: number | null;
    team_b_rounds: number | null;
    created_at: string;
    participants: MatchParticipant[];
}

export interface Tournament {
    id: number;
    name: string;
    status: 'active' | 'ended';
    started_at: string;
    ended_at: string | null;
    matches_count?: number;
}

export interface ModeBreakdown {
    points: number;
    wins: number;
    losses: number;
}

export interface LeaderboardRow {
    player_id: number;
    name: string;
    image_url: string | null;
    points: number;
    matches_played: number;
    wins: number;
    losses: number;
    mode_breakdown: Record<MatchMode, ModeBreakdown>;
}

export interface Achievement {
    key: string;
    name: string;
    description: string;
    player_id: number | null;
    player_name: string;
    detail: string;
}
