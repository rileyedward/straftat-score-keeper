<script setup lang="ts">
import AchievementBadge from '@/components/AchievementBadge.vue';
import LeaderboardCard from '@/components/LeaderboardCard.vue';
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import AppShell from '@/layouts/AppShell.vue';
import type { Achievement, LeaderboardRow, Tournament } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    tournament: Tournament;
    leaderboard: LeaderboardRow[];
    achievements: Achievement[];
}>();

const champion = computed(() => props.leaderboard[0] ?? null);
const fmtDate = (iso: string | null) =>
    iso ? new Date(iso).toLocaleDateString() : '—';
</script>

<template>
    <Head :title="`${tournament.name} — Results`" />

    <AppShell>
        <div class="mx-auto max-w-4xl space-y-8 p-8">
            <header class="text-center">
                <div
                    class="text-xs tracking-widest text-muted-foreground uppercase"
                >
                    Final Results
                </div>
                <h1 class="mt-1 text-4xl font-bold tracking-widest uppercase">
                    {{ tournament.name }}
                </h1>
                <div class="mt-2 text-xs text-muted-foreground">
                    {{ fmtDate(tournament.started_at) }} →
                    {{ fmtDate(tournament.ended_at) }} ·
                    {{ tournament.matches_count }} matches
                </div>
            </header>

            <section
                v-if="champion"
                class="border-2 border-primary bg-card p-8 text-center"
            >
                <div class="text-xs tracking-widest text-primary uppercase">
                    ★ Champion ★
                </div>
                <div class="my-4 flex justify-center">
                    <PlayerAvatar :player="champion" size="lg" />
                </div>
                <div class="text-3xl font-bold tracking-widest uppercase">
                    {{ champion.name }}
                </div>
                <div class="mt-1 font-mono text-sm text-muted-foreground">
                    {{ champion.points }} pts · {{ champion.wins }}-{{
                        champion.losses
                    }}
                </div>
            </section>

            <section v-if="achievements.length" class="space-y-3">
                <h2
                    class="text-sm font-bold tracking-widest text-muted-foreground uppercase"
                >
                    Bonus Stars
                </h2>
                <div class="grid gap-3 md:grid-cols-2">
                    <AchievementBadge
                        v-for="a in achievements"
                        :key="a.key"
                        :achievement="a"
                    />
                </div>
            </section>

            <LeaderboardCard :rows="leaderboard" />

            <div class="flex justify-between">
                <Link
                    href="/tournaments"
                    class="border-2 border-border px-4 py-2 text-xs tracking-wider text-muted-foreground uppercase hover:border-foreground hover:text-foreground"
                >
                    ← Archive
                </Link>
                <Link
                    href="/"
                    class="border-2 border-primary bg-primary px-4 py-2 text-xs font-bold tracking-widest text-primary-foreground uppercase hover:bg-background hover:text-primary"
                >
                    New Tournament
                </Link>
            </div>
        </div>
    </AppShell>
</template>
