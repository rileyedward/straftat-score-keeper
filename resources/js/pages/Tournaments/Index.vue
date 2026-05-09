<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head, Link } from '@inertiajs/vue3';

interface PaginatedTournament {
    id: number;
    name: string;
    started_at: string;
    ended_at: string | null;
    matches_count: number;
}

defineProps<{
    tournaments: {
        data: PaginatedTournament[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const fmt = (iso: string | null) =>
    iso ? new Date(iso).toLocaleDateString() : '—';
</script>

<template>
    <Head title="History" />

    <AppShell>
        <div class="mx-auto max-w-3xl space-y-4 p-8">
            <header class="border-b-2 border-border pb-4">
                <h1 class="text-3xl font-bold tracking-widest uppercase">
                    Archive
                </h1>
                <p
                    class="mt-1 text-xs tracking-wider text-muted-foreground uppercase"
                >
                    Past game nights
                </p>
            </header>

            <div
                v-if="!tournaments.data.length"
                class="border-2 border-border bg-card p-8 text-center text-sm text-muted-foreground"
            >
                No ended tournaments yet.
            </div>

            <div v-else class="space-y-2">
                <Link
                    v-for="t in tournaments.data"
                    :key="t.id"
                    :href="`/tournaments/${t.id}/results`"
                    class="block border-2 border-border bg-card p-4 transition-colors hover:border-primary hover:text-primary"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <div
                                class="text-lg font-bold tracking-wide uppercase"
                            >
                                {{ t.name }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ fmt(t.started_at) }} →
                                {{ fmt(t.ended_at) }} ·
                                {{ t.matches_count }} matches
                            </div>
                        </div>
                        <span
                            class="text-xs tracking-wider text-muted-foreground uppercase"
                            >View →</span
                        >
                    </div>
                </Link>
            </div>

            <nav v-if="tournaments.links?.length > 3" class="flex gap-1">
                <Link
                    v-for="link in tournaments.links"
                    :key="link.label"
                    :href="link.url ?? ''"
                    :class="[
                        'border-2 px-3 py-1 text-xs tracking-wider uppercase',
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border text-muted-foreground hover:border-foreground',
                        !link.url ? 'pointer-events-none opacity-50' : '',
                    ]"
                    v-html="link.label"
                />
            </nav>
        </div>
    </AppShell>
</template>
