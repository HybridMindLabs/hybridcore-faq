<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CircleHelp, Search, ChevronDown, ThumbsUp, ThumbsDown, Check, Link2 } from '@lucide/vue';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useTheme } from '@/composables/useTheme';
import { useLocale } from '@/composables/useLocale';

interface Entry { id: number; question: string; answer_html: string }
interface Group { category: string; entries: Entry[] }

const props = defineProps<{ groups: Group[] }>();

const { theme } = useTheme();
const { t } = useLocale();
const dark = computed(() => theme.value === 'dark');

const textPri = computed(() => dark.value ? 'text-zinc-100' : 'text-zinc-900');
const textMute = computed(() => dark.value ? 'text-zinc-500' : 'text-zinc-400');
const panel = computed(() => dark.value ? 'border-zinc-800/70 bg-[#111113]' : 'border-zinc-200 bg-white');

const VOTED_KEY = 'faq.voted';

const query = ref('');
const open = ref<Set<number>>(new Set());
const voted = ref<Set<number>>(loadVoted());
const copiedId = ref<number | null>(null);

function loadVoted(): Set<number> {
    try {
        const raw = localStorage.getItem(VOTED_KEY);
        return raw ? new Set(JSON.parse(raw)) : new Set();
    } catch {
        return new Set();
    }
}
function saveVoted() {
    try {
        localStorage.setItem(VOTED_KEY, JSON.stringify([...voted.value]));
    } catch {
        // Private browsing / storage disabled — feedback still posts, it just
        // won't be remembered as "already voted" on a future visit.
    }
}

function toggle(id: number) {
    open.value.has(id) ? open.value.delete(id) : open.value.add(id);
}

function vote(entry: Entry, helpful: boolean) {
    if (voted.value.has(entry.id)) return;
    voted.value.add(entry.id);
    saveVoted();
    router.post(route('faq.feedback', entry.id), { helpful }, { preserveScroll: true, preserveState: true });
}

async function copyLink(id: number) {
    const url = `${window.location.origin}${window.location.pathname}#faq-${id}`;
    try {
        await navigator.clipboard.writeText(url);
        copiedId.value = id;
        setTimeout(() => { if (copiedId.value === id) copiedId.value = null; }, 1500);
    } catch {
        // Clipboard API unavailable (no permission, insecure context) — the
        // link is still visible/selectable in the address bar via the hash.
    }
}

function stripHtml(html: string): string {
    return html.replace(/<[^>]+>/g, ' ');
}

const filteredGroups = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return props.groups;

    return props.groups
        .map((group) => ({
            category: group.category,
            entries: group.entries.filter((e) =>
                e.question.toLowerCase().includes(q) || stripHtml(e.answer_html).toLowerCase().includes(q)),
        }))
        .filter((group) => group.entries.length > 0);
});

// Searching by answer text is only useful if the matching answer is visible —
// auto-expand entries whose match isn't in the question itself.
watch(filteredGroups, (groups) => {
    if (!query.value.trim()) return;
    const q = query.value.trim().toLowerCase();
    for (const group of groups) {
        for (const entry of group.entries) {
            if (!entry.question.toLowerCase().includes(q)) open.value.add(entry.id);
        }
    }
});

const totalCount = computed(() => props.groups.reduce((sum, g) => sum + g.entries.length, 0));

// Deep link: /faq#faq-123 opens and scrolls to that question.
onMounted(() => {
    const hash = window.location.hash;
    const match = hash.match(/^#faq-(\d+)$/);
    if (!match) return;

    const id = Number(match[1]);
    open.value.add(id);
    nextTick(() => {
        document.getElementById(`faq-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
});
</script>

<template>
    <Head :title="t('ext.faq.title')" />
    <PublicLayout>
        <div class="border-b" :class="dark ? 'border-zinc-800/60 bg-[#09090b]' : 'border-zinc-200 bg-zinc-50'">
            <div class="max-w-[900px] mx-auto px-4 sm:px-6 py-14">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border text-[11px] font-bold uppercase tracking-widest mb-5"
                    :class="dark ? 'border-blue-500/25 bg-blue-500/8 text-blue-400' : 'border-blue-400/30 bg-blue-50 text-blue-600'">
                    <CircleHelp :size="11" :stroke-width="2.2" /> {{ t('ext.faq.nav') }}
                </div>
                <h1 class="text-4xl font-black tracking-tight" :class="textPri">{{ t('ext.faq.title') }}</h1>
                <p class="mt-3 text-[15px] max-w-lg" :class="textMute">{{ t('ext.faq.subtitle') }}</p>

                <div class="mt-6 relative max-w-md">
                    <Search :size="15" :stroke-width="2" class="absolute left-3.5 top-1/2 -translate-y-1/2" :class="textMute" />
                    <input v-model="query" type="text" :placeholder="t('ext.faq.search_placeholder')"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border text-sm"
                        :class="[panel, dark ? 'text-zinc-200 placeholder:text-zinc-600' : 'text-zinc-900 placeholder:text-zinc-400']" />
                </div>
            </div>
        </div>

        <div class="max-w-[900px] mx-auto px-4 sm:px-6 py-10">
            <div v-if="!totalCount" class="rounded-xl border px-5 py-14 text-center" :class="panel">
                <CircleHelp :size="24" :stroke-width="1.5" class="mx-auto mb-2" :class="textMute" />
                <p class="text-[13px]" :class="textMute">{{ t('ext.faq.no_entries') }}</p>
            </div>

            <div v-else-if="!filteredGroups.length" class="rounded-xl border px-5 py-14 text-center" :class="panel">
                <Search :size="24" :stroke-width="1.5" class="mx-auto mb-2" :class="textMute" />
                <p class="text-[13px]" :class="textMute">{{ t('ext.faq.no_results') }}</p>
            </div>

            <div v-for="group in filteredGroups" :key="group.category" class="mb-8 last:mb-0">
                <p class="text-[11px] font-bold uppercase tracking-[0.15em] mb-3" :class="textMute">{{ group.category }}</p>
                <div class="rounded-xl border overflow-hidden" :class="panel">
                    <div v-for="entry in group.entries" :key="entry.id" :id="`faq-${entry.id}`" class="border-b last:border-0 scroll-mt-24" :class="dark ? 'border-zinc-800/50' : 'border-zinc-100'">
                        <div class="w-full flex items-center gap-2 px-5 py-1 transition-colors" :class="dark ? 'hover:bg-white/[0.02]' : 'hover:bg-zinc-50'">
                            <button type="button" class="flex-1 flex items-center justify-between gap-3 py-3 text-left" @click="toggle(entry.id)">
                                <span class="text-[14px] font-semibold" :class="textPri">{{ entry.question }}</span>
                            </button>
                            <button type="button" :title="t('ext.faq.copy_link')" class="shrink-0 p-1.5 rounded-lg transition-colors"
                                :class="dark ? 'text-zinc-600 hover:text-zinc-300 hover:bg-zinc-800' : 'text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100'"
                                @click="copyLink(entry.id)">
                                <Check v-if="copiedId === entry.id" :size="14" :stroke-width="2" class="text-emerald-400" />
                                <Link2 v-else :size="14" :stroke-width="2" />
                            </button>
                            <button type="button" class="shrink-0 p-1" @click="toggle(entry.id)">
                                <ChevronDown :size="16" :stroke-width="2" class="transition-transform"
                                    :class="[textMute, open.has(entry.id) ? 'rotate-180' : '']" />
                            </button>
                        </div>
                        <div v-if="open.has(entry.id)" class="px-5 pb-5">
                            <div class="text-[13px] leading-relaxed [&_p]:mb-2.5 [&_p:last-child]:mb-0 [&_ul]:list-disc [&_ol]:list-decimal [&_ul]:pl-5 [&_ol]:pl-5 [&_li]:mb-1 [&_strong]:font-bold [&_code]:font-mono [&_code]:text-[12px] [&_code]:px-1 [&_code]:py-0.5 [&_code]:rounded"
                                :class="dark
                                    ? 'text-zinc-400 [&_strong]:text-zinc-200 [&_a]:text-blue-400 [&_code]:bg-zinc-900 [&_code]:text-zinc-300'
                                    : 'text-zinc-600 [&_strong]:text-zinc-900 [&_a]:text-blue-600 [&_code]:bg-zinc-100 [&_code]:text-zinc-700'"
                                v-html="entry.answer_html" />

                            <div class="flex items-center gap-3 mt-4 pt-4 border-t" :class="dark ? 'border-zinc-800/50' : 'border-zinc-100'">
                                <span v-if="voted.has(entry.id)" class="flex items-center gap-1.5 text-[12px] text-emerald-400">
                                    <Check :size="13" :stroke-width="2" /> {{ t('ext.faq.feedback_thanks') }}
                                </span>
                                <template v-else>
                                    <span class="text-[12px]" :class="textMute">{{ t('ext.faq.feedback_question') }}</span>
                                    <button type="button" class="flex items-center gap-1 text-[12px] font-semibold transition-colors"
                                        :class="dark ? 'text-zinc-400 hover:text-emerald-400' : 'text-zinc-500 hover:text-emerald-600'"
                                        @click="vote(entry, true)">
                                        <ThumbsUp :size="13" :stroke-width="2" /> {{ t('ext.faq.feedback_yes') }}
                                    </button>
                                    <button type="button" class="flex items-center gap-1 text-[12px] font-semibold transition-colors"
                                        :class="dark ? 'text-zinc-400 hover:text-red-400' : 'text-zinc-500 hover:text-red-500'"
                                        @click="vote(entry, false)">
                                        <ThumbsDown :size="13" :stroke-width="2" /> {{ t('ext.faq.feedback_no') }}
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
