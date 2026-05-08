<script setup lang="ts">
import AddMatchWizard from '@/components/AddMatchWizard.vue';
import LeaderboardCard from '@/components/LeaderboardCard.vue';
import MatchListItem from '@/components/MatchListItem.vue';
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import AppShell from '@/layouts/AppShell.vue';
import type { LeaderboardRow, MatchRecord, Player, Tournament } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

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
                        class="border-2 border-destructive bg-transparent px-4 py-2 text-xs font-bold uppercase tracking-widest text-destructive transition-colors hover:bg-destructive hover:text-destructive-foreground"
                        @click="endTournament"
                    >
                        End Tournament
                    </button>
                </div>
            </header>

            <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
                <div class="space-y-3">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-muted-foreground">Match Log</h2>
                    <div v-if="!matches.length" class="border-2 border-border bg-card p-6 text-center text-sm text-muted-foreground">
                        No matches yet. Hit "Add Match" to record the first one.
                    </div>
                    <MatchListItem
                        v-for="match in matches"
                        :key="match.id"
                        :match="match"
                        :can-edit="isActive"
                        @edit="openEdit"
                    />
                </div>

                <aside class="space-y-3">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-muted-foreground">Roster</h2>
                    <div class="grid grid-cols-2 gap-2">
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
