<script setup lang="ts">
import AddMatchWizard from '@/components/AddMatchWizard.vue';
import LeaderboardCard from '@/components/LeaderboardCard.vue';
import MatchListItem from '@/components/MatchListItem.vue';
import PlayerAutocomplete from '@/components/PlayerAutocomplete.vue';
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import AppShell from '@/layouts/AppShell.vue';
import type { LeaderboardRow, MatchMode, MatchRecord, Player, Tournament } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    tournament: Tournament;
    roster: Player[];
    matches: MatchRecord[];
    leaderboard: LeaderboardRow[];
}>();

const wizardOpen = ref(false);
const editTarget = ref<MatchRecord | null>(null);

const openCreate = () => {
    editTarget.value = null;
    wizardOpen.value = true;
};

const openEdit = (match: MatchRecord) => {
    editTarget.value = match;
    wizardOpen.value = true;
};

const closeWizard = () => {
    wizardOpen.value = false;
    editTarget.value = null;
};

const endTournament = () => {
    if (!confirm(`End "${props.tournament.name}" and crown the winner?`)) return;
    router.post(`/tournaments/${props.tournament.id}/end`);
};

const isActive = props.tournament.status === 'active';

const addingPlayers = ref(false);
const pendingPlayers = ref<Player[]>([]);
const addPlayerForm = useForm({ player_ids: [] as number[] });

const rosterIds = computed(() => props.roster.map((p) => p.id));
const excludeIds = computed(() => [...rosterIds.value, ...pendingPlayers.value.map((p) => p.id)]);

const queuePlayer = (player: Player) => {
    if (pendingPlayers.value.find((p) => p.id === player.id)) return;
    pendingPlayers.value.push(player);
    addPlayerForm.player_ids = pendingPlayers.value.map((p) => p.id);
};

const removePending = (id: number) => {
    pendingPlayers.value = pendingPlayers.value.filter((p) => p.id !== id);
    addPlayerForm.player_ids = pendingPlayers.value.map((p) => p.id);
};

const submitAddPlayers = () => {
    addPlayerForm.post(`/tournaments/${props.tournament.id}/players`, {
        onSuccess: () => {
            addingPlayers.value = false;
            pendingPlayers.value = [];
            addPlayerForm.reset();
        },
    });
};

type MatchFilter = 'all' | MatchMode;
const matchFilter = ref<MatchFilter>('all');
const matchFilters: Array<{ key: MatchFilter; label: string }> = [
    { key: 'all', label: 'All' },
    { key: '1v1', label: '1v1' },
    { key: '2v2', label: '2v2' },
    { key: 'ffa', label: 'FFA' },
];
const filteredMatches = computed(() =>
    matchFilter.value === 'all' ? props.matches : props.matches.filter((m) => m.mode === matchFilter.value),
);
</script>

<template>
    <Head :title="tournament.name" />

    <AppShell>
        <div class="mx-auto max-w-5xl space-y-6 p-8">
            <header class="flex flex-wrap items-end justify-between gap-4 border-b-2 border-border pb-4">
                <div>
                    <div class="text-xs uppercase tracking-widest text-muted-foreground">
                        {{ tournament.status === 'active' ? 'Active Tournament' : 'Ended Tournament' }}
                    </div>
                    <h1 class="text-4xl font-bold uppercase tracking-widest text-foreground">
                        {{ tournament.name }}
                    </h1>
                    <div class="mt-1 text-xs text-muted-foreground">
                        {{ matches.length }} match{{ matches.length === 1 ? '' : 'es' }} ·
                        {{ roster.length }} players
                    </div>
                </div>
                <div v-if="isActive" class="flex gap-2">
                    <button
                        type="button"
                        class="border-2 border-primary bg-primary px-4 py-2 text-xs font-bold uppercase tracking-widest text-primary-foreground transition-colors hover:bg-background hover:text-primary"
                        @click="openCreate"
                    >
                        + Add Match
                    </button>
                    <button
                        type="button"
                        class="border-2 border-primary bg-transparent px-4 py-2 text-xs font-bold uppercase tracking-widest text-primary transition-colors hover:bg-primary hover:text-primary-foreground"
                        @click="endTournament"
                    >
                        End Tournament
                    </button>
                </div>
            </header>

            <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold uppercase tracking-widest text-muted-foreground">Match Log</h2>
                        <div class="flex gap-2">
                            <button
                                v-for="f in matchFilters"
                                :key="f.key"
                                type="button"
                                :class="[
                                    'border-2 px-2 py-1 text-xs uppercase tracking-wider transition-colors',
                                    matchFilter === f.key
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground',
                                ]"
                                @click="matchFilter = f.key"
                            >
                                {{ f.label }}
                            </button>
                        </div>
                    </div>
                    <div v-if="!matches.length" class="border-2 border-border bg-card p-6 text-center text-sm text-muted-foreground">
                        No matches yet. Hit "Add Match" to record the first one.
                    </div>
                    <div v-else-if="!filteredMatches.length" class="border-2 border-border bg-card p-6 text-center text-sm text-muted-foreground">
                        No {{ matchFilter }} matches recorded yet.
                    </div>
                    <MatchListItem
                        v-for="match in filteredMatches"
                        :key="match.id"
                        :match="match"
                        :can-edit="isActive"
                        @edit="openEdit"
                    />
                </div>

                <aside class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold uppercase tracking-widest text-muted-foreground">Roster</h2>
                        <button
                            v-if="isActive"
                            type="button"
                            class="border-2 border-border px-2 py-1 text-xs uppercase tracking-wider text-muted-foreground transition-colors hover:border-foreground hover:text-foreground"
                            @click="addingPlayers = !addingPlayers"
                        >
                            {{ addingPlayers ? 'Cancel' : '+ Add' }}
                        </button>
                    </div>

                    <div class="flex flex-col gap-2">
                        <a
                            v-for="p in roster"
                            :key="p.id"
                            :href="`/players/${p.id}`"
                            class="flex items-center gap-2 border-2 border-border bg-card px-3 py-2 transition-colors hover:border-foreground"
                        >
                            <PlayerAvatar :player="p" size="sm" />
                            <span class="truncate text-sm">{{ p.name }}</span>
                        </a>
                    </div>

                    <div v-if="addingPlayers" class="space-y-2 border-2 border-border bg-card p-3">
                        <PlayerAutocomplete :exclude-ids="excludeIds" placeholder="Type a name..." @select="queuePlayer" />
                        <div v-if="pendingPlayers.length" class="flex flex-col gap-1">
                            <div
                                v-for="p in pendingPlayers"
                                :key="p.id"
                                class="flex items-center justify-between border border-border px-2 py-1"
                            >
                                <div class="flex items-center gap-2">
                                    <PlayerAvatar :player="p" size="sm" />
                                    <span class="text-sm">{{ p.name }}</span>
                                </div>
                                <button
                                    type="button"
                                    class="text-xs text-muted-foreground hover:text-destructive"
                                    @click="removePending(p.id)"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                        <button
                            type="button"
                            :disabled="!pendingPlayers.length || addPlayerForm.processing"
                            class="w-full border-2 border-primary bg-primary px-3 py-1.5 text-xs font-bold uppercase tracking-widest text-primary-foreground transition-colors hover:bg-background hover:text-primary disabled:opacity-50"
                            @click="submitAddPlayers"
                        >
                            Add {{ pendingPlayers.length || '' }} Player{{ pendingPlayers.length === 1 ? '' : 's' }}
                        </button>
                    </div>
                </aside>
            </div>

            <LeaderboardCard :rows="leaderboard" />
        </div>

        <AddMatchWizard
            v-if="wizardOpen"
            :tournament-id="tournament.id"
            :roster="roster"
            :edit-match="editTarget"
            @close="closeWizard"
        />
    </AppShell>
</template>
