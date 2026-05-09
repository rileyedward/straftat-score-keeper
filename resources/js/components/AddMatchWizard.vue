<script setup lang="ts">
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import type { MatchMode, MatchRecord, Player } from '@/types';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{
    tournamentId: number;
    roster: Player[];
    editMatch?: MatchRecord | null;
}>();

const emit = defineEmits<{
    close: [];
}>();

const step = ref<1 | 2 | 3>(1);
const mode = ref<MatchMode | null>(null);
const teamA = ref<Player[]>([]);
const teamB = ref<Player[]>([]);
const ffa = ref<Player[]>([]);
const oneVOnePlayers = ref<Player[]>([]);

const oneVOneRounds = ref<Record<number, number>>({});
const teamARounds = ref<number>(0);
const teamBRounds = ref<number>(0);
const ffaRounds = ref<Record<number, number>>({});

const submitting = ref(false);
const errors = ref<Record<string, string>>({});

onMounted(() => {
    if (props.editMatch) {
        primeForEdit(props.editMatch);
    }
    document.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
});

const onKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') emit('close');
    if (step.value === 1) {
        if (e.key === '1') pickMode('1v1');
        if (e.key === '2') pickMode('2v2');
        if (e.key === '3') pickMode('ffa');
    }
};

const primeForEdit = (m: MatchRecord) => {
    mode.value = m.mode;
    if (m.mode === '1v1') {
        oneVOnePlayers.value = m.participants
            .map((p) => props.roster.find((r) => r.id === p.player_id))
            .filter(Boolean) as Player[];
        oneVOneRounds.value = Object.fromEntries(m.participants.map((p) => [p.player_id, p.rounds_won]));
    } else if (m.mode === '2v2') {
        teamA.value = m.participants
            .filter((p) => p.team === 'A')
            .map((p) => props.roster.find((r) => r.id === p.player_id))
            .filter(Boolean) as Player[];
        teamB.value = m.participants
            .filter((p) => p.team === 'B')
            .map((p) => props.roster.find((r) => r.id === p.player_id))
            .filter(Boolean) as Player[];
        teamARounds.value = m.team_a_rounds ?? 0;
        teamBRounds.value = m.team_b_rounds ?? 0;
    } else {
        ffa.value = m.participants
            .map((p) => props.roster.find((r) => r.id === p.player_id))
            .filter(Boolean) as Player[];
        ffaRounds.value = Object.fromEntries(m.participants.map((p) => [p.player_id, p.rounds_won]));
    }
    step.value = 3;
};

const pickMode = (m: MatchMode) => {
    mode.value = m;
    step.value = 2;
};

const selectedSet = computed(() => {
    if (mode.value === '1v1') return new Set(oneVOnePlayers.value.map((p) => p.id));
    if (mode.value === '2v2') return new Set([...teamA.value, ...teamB.value].map((p) => p.id));
    return new Set(ffa.value.map((p) => p.id));
});

const togglePlayer = (player: Player) => {
    if (mode.value === '1v1') {
        if (oneVOnePlayers.value.find((p) => p.id === player.id)) {
            oneVOnePlayers.value = oneVOnePlayers.value.filter((p) => p.id !== player.id);
        } else if (oneVOnePlayers.value.length < 2) {
            oneVOnePlayers.value.push(player);
        }
    } else if (mode.value === 'ffa') {
        if (ffa.value.find((p) => p.id === player.id)) {
            ffa.value = ffa.value.filter((p) => p.id !== player.id);
        } else if (ffa.value.length < 4) {
            ffa.value.push(player);
        }
    }
};

const assignTeam = (player: Player, team: 'A' | 'B' | null) => {
    teamA.value = teamA.value.filter((p) => p.id !== player.id);
    teamB.value = teamB.value.filter((p) => p.id !== player.id);
    if (team === 'A' && teamA.value.length < 2) teamA.value.push(player);
    if (team === 'B' && teamB.value.length < 2) teamB.value.push(player);
};

const teamOf = (player: Player): 'A' | 'B' | null => {
    if (teamA.value.find((p) => p.id === player.id)) return 'A';
    if (teamB.value.find((p) => p.id === player.id)) return 'B';
    return null;
};

const canAdvanceFromParticipants = computed(() => {
    if (mode.value === '1v1') return oneVOnePlayers.value.length === 2;
    if (mode.value === '2v2') return teamA.value.length === 2 && teamB.value.length === 2;
    if (mode.value === 'ffa') return ffa.value.length === 3 || ffa.value.length === 4;
    return false;
});

const goToScore = () => {
    if (!canAdvanceFromParticipants.value) return;
    if (mode.value === '1v1') {
        oneVOnePlayers.value.forEach((p) => {
            if (oneVOneRounds.value[p.id] === undefined) oneVOneRounds.value[p.id] = 0;
        });
    }
    if (mode.value === 'ffa') {
        ffa.value.forEach((p) => {
            if (ffaRounds.value[p.id] === undefined) ffaRounds.value[p.id] = 0;
        });
    }
    step.value = 3;
};

const setOneVOneRounds = (playerId: number, r: number) => {
    if (r === 4) {
        oneVOnePlayers.value.forEach((p) => {
            if (p.id !== playerId && oneVOneRounds.value[p.id] === 4) oneVOneRounds.value[p.id] = 0;
        });
    }
    oneVOneRounds.value[playerId] = r;
};

const setTeamRounds = (team: 'A' | 'B', r: number) => {
    if (r === 4) {
        if (team === 'A' && teamBRounds.value === 4) teamBRounds.value = 0;
        if (team === 'B' && teamARounds.value === 4) teamARounds.value = 0;
    }
    if (team === 'A') teamARounds.value = r;
    else teamBRounds.value = r;
};

// FFA helpers
const setFfaRounds = (playerId: number, r: number) => {
    if (r === 4) {
        ffa.value.forEach((p) => {
            if (p.id !== playerId && ffaRounds.value[p.id] === 4) ffaRounds.value[p.id] = 0;
        });
    }
    ffaRounds.value[playerId] = r;
};

const ffaPlacementMap = computed<Record<number, number>>(() => {
    const sorted = [...ffa.value].sort((a, b) => (ffaRounds.value[b.id] ?? 0) - (ffaRounds.value[a.id] ?? 0));
    const out: Record<number, number> = {};
    let prevRounds: number | null = null;
    let prevPlace = 0;
    sorted.forEach((p, i) => {
        const r = ffaRounds.value[p.id] ?? 0;
        const place = r === prevRounds ? prevPlace : i + 1;
        out[p.id] = place;
        prevRounds = r;
        prevPlace = place;
    });
    return out;
});

const ffaPlaceCounts = computed<Record<number, number>>(() => {
    const counts: Record<number, number> = {};
    Object.values(ffaPlacementMap.value).forEach((place) => {
        counts[place] = (counts[place] ?? 0) + 1;
    });
    return counts;
});

const submit = () => {
    submitting.value = true;
    errors.value = {};

    let payload: Record<string, any> = {};

    if (mode.value === '1v1') {
        payload = {
            mode: '1v1',
            participants: oneVOnePlayers.value.map((p) => ({
                player_id: p.id,
                rounds_won: oneVOneRounds.value[p.id] ?? 0,
            })),
        };
    } else if (mode.value === '2v2') {
        payload = {
            mode: '2v2',
            team_a_rounds: teamARounds.value,
            team_b_rounds: teamBRounds.value,
            participants: [
                ...teamA.value.map((p) => ({ player_id: p.id, team: 'A' })),
                ...teamB.value.map((p) => ({ player_id: p.id, team: 'B' })),
            ],
        };
    } else {
        payload = {
            mode: 'ffa',
            participants: ffa.value.map((p) => ({
                player_id: p.id,
                rounds_won: ffaRounds.value[p.id] ?? 0,
            })),
        };
    }

    const url = props.editMatch
        ? `/matches/${props.editMatch.id}`
        : `/tournaments/${props.tournamentId}/matches`;
    const method = props.editMatch ? 'patch' : 'post';

    router[method](url, payload, {
        preserveScroll: true,
        onSuccess: () => emit('close'),
        onError: (e) => {
            errors.value = e as Record<string, string>;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
};

const back = () => {
    if (step.value === 1) emit('close');
    else step.value = ((step.value as number) - 1) as 1 | 2 | 3;
};
</script>

<template>
    <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/70 p-4" @click.self="emit('close')">
        <div class="w-full max-w-2xl border-2 border-border bg-background">
            <header class="flex items-center justify-between border-b-2 border-border p-4">
                <h2 class="text-lg font-bold uppercase tracking-widest">
                    {{ editMatch ? 'Edit Match' : 'New Match' }}
                    <span class="ml-2 text-xs text-muted-foreground">Step {{ step }} / 3</span>
                </h2>
                <button
                    type="button"
                    class="text-xs uppercase tracking-wider text-muted-foreground hover:text-foreground"
                    @click="emit('close')"
                >
                    Close [Esc]
                </button>
            </header>

            <div class="space-y-4 p-6">
                <div v-if="step === 1" class="space-y-3">
                    <p class="text-xs uppercase tracking-wider text-muted-foreground">Pick a mode (1/2/3)</p>
                    <div class="grid grid-cols-3 gap-3">
                        <button
                            type="button"
                            class="border-2 border-border bg-card p-6 text-center font-bold uppercase tracking-widest text-foreground transition-colors hover:border-primary hover:text-primary"
                            @click="pickMode('1v1')"
                        >
                            1v1
                            <div class="mt-1 text-xs text-muted-foreground">2 players</div>
                        </button>
                        <button
                            type="button"
                            class="border-2 border-border bg-card p-6 text-center font-bold uppercase tracking-widest text-foreground transition-colors hover:border-primary hover:text-primary"
                            @click="pickMode('2v2')"
                        >
                            2v2
                            <div class="mt-1 text-xs text-muted-foreground">2 teams of 2</div>
                        </button>
                        <button
                            type="button"
                            class="border-2 border-border bg-card p-6 text-center font-bold uppercase tracking-widest text-foreground transition-colors hover:border-primary hover:text-primary"
                            @click="pickMode('ffa')"
                        >
                            FFA
                            <div class="mt-1 text-xs text-muted-foreground">3-4 players</div>
                        </button>
                    </div>
                </div>

                <div v-if="step === 2" class="space-y-3">
                    <p class="text-xs uppercase tracking-wider text-muted-foreground">
                        <template v-if="mode === '1v1'">Pick 2 players</template>
                        <template v-else-if="mode === '2v2'">Assign 4 players to teams A & B</template>
                        <template v-else>Pick 3 or 4 players</template>
                    </p>

                    <div v-if="mode !== '2v2'" class="grid grid-cols-2 gap-2 md:grid-cols-3">
                        <button
                            v-for="p in roster"
                            :key="p.id"
                            type="button"
                            :class="[
                                'flex items-center gap-2 border-2 px-3 py-2 text-left transition-colors',
                                selectedSet.has(p.id)
                                    ? 'border-primary bg-primary/20 text-foreground'
                                    : 'border-border bg-card text-muted-foreground hover:border-foreground hover:text-foreground',
                            ]"
                            @click="togglePlayer(p)"
                        >
                            <PlayerAvatar :player="p" size="sm" />
                            <span class="truncate text-sm">{{ p.name }}</span>
                        </button>
                    </div>

                    <div v-else class="grid gap-3 md:grid-cols-2">
                        <div v-for="p in roster" :key="p.id" class="flex items-center justify-between border-2 border-border bg-card px-3 py-2">
                            <div class="flex items-center gap-2">
                                <PlayerAvatar :player="p" size="sm" />
                                <span class="text-sm">{{ p.name }}</span>
                            </div>
                            <div class="flex gap-1">
                                <button
                                    type="button"
                                    :class="[
                                        'border-2 px-2 py-1 text-xs font-bold uppercase tracking-wider',
                                        teamOf(p) === 'A'
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-border text-muted-foreground hover:border-primary hover:text-primary',
                                    ]"
                                    @click="assignTeam(p, teamOf(p) === 'A' ? null : 'A')"
                                >
                                    A
                                </button>
                                <button
                                    type="button"
                                    :class="[
                                        'border-2 px-2 py-1 text-xs font-bold uppercase tracking-wider',
                                        teamOf(p) === 'B'
                                            ? 'border-accent bg-accent text-accent-foreground'
                                            : 'border-border text-muted-foreground hover:border-accent hover:text-accent',
                                    ]"
                                    @click="assignTeam(p, teamOf(p) === 'B' ? null : 'B')"
                                >
                                    B
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="step === 3" class="space-y-4">
                    <p class="text-xs uppercase tracking-wider text-muted-foreground">Score (first to 4)</p>

                    <!-- 1v1 -->
                    <div v-if="mode === '1v1'" class="space-y-2">
                        <div
                            v-for="p in oneVOnePlayers"
                            :key="p.id"
                            class="flex items-center gap-3 border-2 border-border bg-card px-3 py-2"
                        >
                            <span class="min-w-0 flex-1 truncate font-medium">{{ p.name }}</span>
                            <div class="flex shrink-0 gap-1">
                                <button
                                    v-for="r in [0, 1, 2, 3, 4]"
                                    :key="r"
                                    type="button"
                                    :class="[
                                        'w-9 border-2 py-1.5 text-center font-mono text-sm font-bold transition-colors',
                                        oneVOneRounds[p.id] === r
                                            ? r === 4
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-foreground bg-foreground text-background'
                                            : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground',
                                    ]"
                                    @click="setOneVOneRounds(p.id, r)"
                                >
                                    {{ r }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2v2 -->
                    <div v-if="mode === '2v2'" class="space-y-2">
                        <div class="flex items-center gap-3 border-2 border-border bg-card px-3 py-2">
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold uppercase tracking-widest text-primary">Team A</div>
                                <div class="truncate text-sm text-muted-foreground">{{ teamA.map((p) => p.name).join(', ') }}</div>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <button
                                    v-for="r in [0, 1, 2, 3, 4]"
                                    :key="r"
                                    type="button"
                                    :class="[
                                        'w-9 border-2 py-1.5 text-center font-mono text-sm font-bold transition-colors',
                                        teamARounds === r
                                            ? r === 4 ? 'border-primary bg-primary text-primary-foreground' : 'border-foreground bg-foreground text-background'
                                            : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground',
                                    ]"
                                    @click="setTeamRounds('A', r)"
                                >
                                    {{ r }}
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 border-2 border-border bg-card px-3 py-2">
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold uppercase tracking-widest text-accent">Team B</div>
                                <div class="truncate text-sm text-muted-foreground">{{ teamB.map((p) => p.name).join(', ') }}</div>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <button
                                    v-for="r in [0, 1, 2, 3, 4]"
                                    :key="r"
                                    type="button"
                                    :class="[
                                        'w-9 border-2 py-1.5 text-center font-mono text-sm font-bold transition-colors',
                                        teamBRounds === r
                                            ? r === 4 ? 'border-primary bg-primary text-primary-foreground' : 'border-foreground bg-foreground text-background'
                                            : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground',
                                    ]"
                                    @click="setTeamRounds('B', r)"
                                >
                                    {{ r }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- FFA -->
                    <div v-if="mode === 'ffa'" class="space-y-2">
                        <p class="text-xs text-muted-foreground">Select rounds for each player. Place updates live — row order stays fixed.</p>
                        <div
                            v-for="p in ffa"
                            :key="p.id"
                            class="flex items-center gap-3 border-2 border-border bg-card px-3 py-2"
                        >
                            <span
                                :class="[
                                    'inline-flex w-10 shrink-0 items-center justify-center border-2 py-1 text-xs font-bold uppercase tracking-wider',
                                    ffaPlacementMap[p.id] === 1
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : ffaPlaceCounts[ffaPlacementMap[p.id]] > 1
                                          ? 'border-accent text-accent'
                                          : 'border-border text-muted-foreground',
                                ]"
                            >
                                {{ ffaPlaceCounts[ffaPlacementMap[p.id]] > 1 ? `T${ffaPlacementMap[p.id]}` : ffaPlacementMap[p.id] }}
                            </span>
                            <span class="min-w-0 flex-1 truncate font-medium">{{ p.name }}</span>
                            <div class="flex shrink-0 gap-1">
                                <button
                                    v-for="r in [0, 1, 2, 3, 4]"
                                    :key="r"
                                    type="button"
                                    :class="[
                                        'w-9 border-2 py-1.5 text-center font-mono text-sm font-bold transition-colors',
                                        ffaRounds[p.id] === r
                                            ? r === 4
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-foreground bg-foreground text-background'
                                            : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground',
                                    ]"
                                    @click="setFfaRounds(p.id, r)"
                                >
                                    {{ r }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="Object.keys(errors).length" class="border-2 border-destructive bg-destructive/10 p-3 text-sm">
                        <div v-for="(msg, key) in errors" :key="key">{{ msg }}</div>
                    </div>
                </div>
            </div>

            <footer class="flex items-center justify-between border-t-2 border-border p-4">
                <button
                    type="button"
                    class="border-2 border-border px-3 py-1 text-xs uppercase tracking-wider text-muted-foreground transition-colors hover:border-foreground hover:text-foreground"
                    @click="back"
                >
                    {{ step === 1 ? 'Cancel' : 'Back' }}
                </button>

                <button
                    v-if="step === 2"
                    type="button"
                    :disabled="!canAdvanceFromParticipants"
                    class="border-2 border-primary bg-primary px-4 py-2 text-xs font-bold uppercase tracking-widest text-primary-foreground transition-colors hover:bg-background hover:text-primary disabled:opacity-50"
                    @click="goToScore"
                >
                    Next →
                </button>

                <button
                    v-if="step === 3"
                    type="button"
                    :disabled="submitting"
                    class="border-2 border-primary bg-primary px-4 py-2 text-xs font-bold uppercase tracking-widest text-primary-foreground transition-colors hover:bg-background hover:text-primary disabled:opacity-50"
                    @click="submit"
                >
                    {{ editMatch ? 'Save' : 'Record Match' }}
                </button>
            </footer>
        </div>
    </div>
</template>
