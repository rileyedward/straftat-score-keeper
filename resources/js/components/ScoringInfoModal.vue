<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';

const emit = defineEmits<{
    close: [];
}>();

const onKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') emit('close');
};

onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div
        class="fixed inset-0 z-40 flex items-center justify-center bg-black/70 p-4"
        @click.self="emit('close')"
    >
        <div class="w-full max-w-md border-2 border-border bg-background">
            <header
                class="flex items-center justify-between border-b-2 border-border px-4 py-3"
            >
                <h2 class="text-sm font-bold tracking-widest uppercase">
                    Scoring
                </h2>
                <button
                    type="button"
                    class="text-xs tracking-wider text-muted-foreground uppercase hover:text-foreground"
                    @click="emit('close')"
                >
                    [Esc]
                </button>
            </header>

            <div class="space-y-4 p-4">
                <section>
                    <div
                        class="mb-2 text-xs font-bold tracking-widest text-primary uppercase"
                    >
                        1v1 &amp; 2v2
                    </div>
                    <table class="w-full font-mono text-sm">
                        <tbody>
                            <tr class="border-b border-border">
                                <td class="py-1.5 text-muted-foreground">
                                    4-0 / 4-1 / 4-2
                                </td>
                                <td class="py-1.5 text-right">
                                    <span class="font-bold text-primary"
                                        >3</span
                                    >
                                    <span class="text-muted-foreground">
                                        /
                                    </span>
                                    <span class="font-bold">0</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="py-1.5 text-muted-foreground">
                                    4-3 <span class="text-accent">close</span>
                                </td>
                                <td class="py-1.5 text-right">
                                    <span class="font-bold text-primary"
                                        >2</span
                                    >
                                    <span class="text-muted-foreground">
                                        /
                                    </span>
                                    <span class="font-bold">1</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section>
                    <div
                        class="mb-2 text-xs font-bold tracking-widest text-primary uppercase"
                    >
                        FFA
                    </div>
                    <table class="w-full font-mono text-sm">
                        <tbody>
                            <tr class="border-b border-border">
                                <td class="py-1.5 text-muted-foreground">
                                    4 players
                                </td>
                                <td class="py-1.5 text-right font-bold">
                                    <span class="text-primary">3</span> · 2 · 1
                                    ·
                                    <span class="text-muted-foreground">0</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="py-1.5 text-muted-foreground">
                                    3 players
                                </td>
                                <td class="py-1.5 text-right font-bold">
                                    <span class="text-primary">2</span> · 1 ·
                                    <span class="text-muted-foreground">0</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <p
                    class="border-t-2 border-border pt-3 text-xs text-muted-foreground"
                >
                    FFA ties take the lower placement (e.g. tied 2nd → both
                    3rd).
                </p>
            </div>
        </div>
    </div>
</template>
