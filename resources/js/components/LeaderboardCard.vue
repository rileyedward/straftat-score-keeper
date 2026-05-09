<script setup lang="ts">
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import ScoringInfoModal from '@/components/ScoringInfoModal.vue';
import type { LeaderboardRow, MatchMode } from '@/types';
import { computed, ref } from 'vue';

const props = defineProps<{
    rows: LeaderboardRow[];
}>();

const filter = ref<'overall' | MatchMode>('overall');
const showScoringInfo = ref(false);

const sorted = computed(() => {
    if (filter.value === 'overall') {
        return [...props.rows];
    }
    return [...props.rows].sort((a, b) => {
        const ap = a.mode_breakdown[filter.value as MatchMode].points;
        const bp = b.mode_breakdown[filter.value as MatchMode].points;
        return bp - ap;
    });
});

const filters: Array<{ key: 'overall' | MatchMode; label: string }> = [
    { key: 'overall', label: 'Overall' },
    { key: '1v1', label: '1v1' },
    { key: '2v2', label: '2v2' },
    { key: 'ffa', label: 'FFA' },
];

const showPoints = (row: LeaderboardRow) => {
    if (filter.value === 'overall') return row.points;
    return row.mode_breakdown[filter.value as MatchMode].points;
};

const showWL = (row: LeaderboardRow) => {
    if (filter.value === 'overall') return `${row.wins}-${row.losses}`;
    const m = row.mode_breakdown[filter.value as MatchMode];
    return `${m.wins}-${m.losses}`;
};
</script>

<template>
    <div class="border-2 border-border bg-card">
        <div class="flex items-center justify-between border-b-2 border-border p-4">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-bold uppercase tracking-widest">Leaderboard</h2>
                <button
                    type="button"
                    title="How scoring works"
                    aria-label="How scoring works"
                    class="flex h-6 w-6 items-center justify-center border-2 border-border text-xs font-bold text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                    @click="showScoringInfo = true"
                >
                    ?
                </button>
            </div>
            <div class="flex gap-2">
                <button
                    v-for="f in filters"
                    :key="f.key"
                    type="button"
                    :class="[
                        'border-2 px-2 py-1 text-xs uppercase tracking-wider transition-colors',
                        filter === f.key
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground',
                    ]"
                    @click="filter = f.key"
                >
                    {{ f.label }}
                </button>
            </div>
        </div>

        <div v-if="sorted.length === 0" class="p-6 text-center text-sm text-muted-foreground">
            No matches yet. Record one to start the leaderboard.
        </div>

        <table v-else class="w-full">
            <thead class="text-xs uppercase tracking-wider text-muted-foreground">
                <tr class="border-b-2 border-border">
                    <th class="px-3 py-2 text-left">#</th>
                    <th class="px-3 py-2 text-left">Player</th>
                    <th class="px-3 py-2 text-right">Points</th>
                    <th class="px-3 py-2 text-right">W-L</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(row, idx) in sorted"
                    :key="row.player_id"
                    class="border-b border-border last:border-b-0"
                >
                    <td class="px-3 py-2 font-bold">{{ idx + 1 }}</td>
                    <td class="px-3 py-2">
                        <a :href="`/players/${row.player_id}`" class="flex items-center gap-2 hover:text-accent">
                            <PlayerAvatar :player="row" size="sm" />
                            <span class="font-medium">{{ row.name }}</span>
                        </a>
                    </td>
                    <td class="px-3 py-2 text-right font-bold text-primary">{{ showPoints(row) }}</td>
                    <td class="px-3 py-2 text-right font-mono text-muted-foreground">{{ showWL(row) }}</td>
                </tr>
            </tbody>
        </table>

        <ScoringInfoModal v-if="showScoringInfo" @close="showScoringInfo = false" />
    </div>
</template>
