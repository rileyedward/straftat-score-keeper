<script setup lang="ts">
import type { MatchRecord } from '@/types';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    match: MatchRecord;
    canEdit?: boolean;
}>();

const emit = defineEmits<{
    edit: [match: MatchRecord];
}>();

const summary = computed(() => {
    if (props.match.mode === '1v1') {
        const sorted = [...props.match.participants].sort((a, b) => b.rounds_won - a.rounds_won);
        return `${sorted[0]?.player_name} ${sorted[0]?.rounds_won} – ${sorted[1]?.rounds_won} ${sorted[1]?.player_name}`;
    }
    if (props.match.mode === '2v2') {
        const a = props.match.participants
            .filter((p) => p.team === 'A')
            .map((p) => p.player_name)
            .join(' & ');
        const b = props.match.participants
            .filter((p) => p.team === 'B')
            .map((p) => p.player_name)
            .join(' & ');
        return `${a} ${props.match.team_a_rounds} – ${props.match.team_b_rounds} ${b}`;
    }
    const sorted = [...props.match.participants].sort((a, b) => (a.placement ?? 99) - (b.placement ?? 99));
    return sorted.map((p) => `${p.placement}. ${p.player_name}(${p.rounds_won})`).join('  ');
});

const remove = () => {
    if (!confirm('Remove this match?')) return;
    router.delete(`/matches/${props.match.id}`, { preserveScroll: true });
};
</script>

<template>
    <div class="flex items-center justify-between gap-3 border-2 border-border bg-card px-4 py-3">
        <div class="flex items-center gap-3">
            <span
                class="border-2 px-2 py-1 text-xs font-bold uppercase tracking-wider"
                :class="
                    match.mode === '1v1'
                        ? 'border-primary text-primary'
                        : match.mode === '2v2'
                        ? 'border-accent text-accent'
                        : 'border-foreground text-foreground'
                "
            >
                {{ match.mode === 'ffa' ? 'FFA' : match.mode }}
            </span>
            <span class="font-mono text-sm">{{ summary }}</span>
        </div>

        <div v-if="canEdit" class="flex gap-1">
            <button
                type="button"
                class="border-2 border-border px-2 py-1 text-xs uppercase tracking-wider text-muted-foreground hover:border-accent hover:text-accent"
                @click="emit('edit', match)"
            >
                Edit
            </button>
            <button
                type="button"
                class="border-2 border-border px-2 py-1 text-xs uppercase tracking-wider text-muted-foreground hover:border-destructive hover:text-destructive"
                @click="remove"
            >
                Remove
            </button>
        </div>
    </div>
</template>
