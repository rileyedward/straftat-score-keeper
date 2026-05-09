<script setup lang="ts">
import AppShell from '@/layouts/AppShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    activeTournament: { id: number; name: string } | null;
    pastTournamentsCount: number;
}>();
</script>

<template>
    <Head title="Home" />

    <AppShell>
        <div class="mx-auto max-w-2xl space-y-6 p-8">
            <header class="border-b-2 border-border pb-4">
                <h1
                    class="text-4xl font-bold tracking-widest text-foreground uppercase"
                >
                    Game Night
                </h1>
                <p
                    class="mt-1 text-xs tracking-wider text-muted-foreground uppercase"
                >
                    Pick your destiny
                </p>
            </header>

            <div class="grid gap-4 md:grid-cols-2">
                <Link
                    v-if="activeTournament"
                    :href="`/tournaments/${activeTournament.id}`"
                    class="block border-2 border-primary bg-primary p-6 transition-colors hover:bg-background hover:text-primary"
                >
                    <div class="text-xs tracking-wider uppercase opacity-80">
                        Resume
                    </div>
                    <div
                        class="mt-2 text-2xl font-bold tracking-wide uppercase"
                    >
                        {{ activeTournament.name }}
                    </div>
                </Link>
                <Link
                    v-else
                    href="/tournaments/create"
                    class="block border-2 border-primary bg-primary p-6 text-primary-foreground transition-colors hover:bg-background hover:text-primary"
                >
                    <div class="text-xs tracking-wider uppercase opacity-80">
                        Start
                    </div>
                    <div
                        class="mt-2 text-2xl font-bold tracking-wide uppercase"
                    >
                        New Tournament
                    </div>
                </Link>

                <Link
                    href="/tournaments"
                    class="block border-2 border-border bg-card p-6 text-foreground transition-colors hover:border-accent hover:text-accent"
                >
                    <div
                        class="text-xs tracking-wider text-muted-foreground uppercase"
                    >
                        Archive
                    </div>
                    <div
                        class="mt-2 text-2xl font-bold tracking-wide uppercase"
                    >
                        History ({{ pastTournamentsCount }})
                    </div>
                </Link>
            </div>
        </div>
    </AppShell>
</template>
