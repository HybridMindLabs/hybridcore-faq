<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CircleHelp, Search, ChevronDown, ThumbsUp, ThumbsDown, Check, Link2, Flame,
    UserCircle, Server, CreditCard, ShieldCheck, Gift, Trophy, MessageCircle,
} from '@lucide/vue';
import { computed, nextTick, onMounted, onUnmounted, ref, watch, type Component } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import Breadcrumb from '@/components/UI/Breadcrumb.vue';
import SocialLinks from '@/components/UI/SocialLinks.vue';
import { useTheme } from '@/composables/useTheme';
import { useLocale } from '@/composables/useLocale';

interface Entry { id: number; slug: string; category: string; question: string; answer_html: string; answer_text: string }
interface Group { category: string; slug: string; entries: Entry[] }

const props = defineProps<{ groups: Group[]; popular: Entry[]; metaDescription: string }>();

const { theme } = useTheme();
const { t } = useLocale();
const dark = computed(() => theme.value === 'dark');

const textPri = computed(() => dark.value ? 'text-zinc-100' : 'text-zinc-900');
const textMute = computed(() => dark.value ? 'text-zinc-500' : 'text-zinc-400');
const panel = computed(() => dark.value ? 'border-zinc-800/70 bg-[#111113]' : 'border-zinc-200 bg-white');
const navBg = computed(() => dark.value ? 'border-zinc-800/60 bg-[#0a0a0c]/95' : 'border-zinc-200 bg-white/95');

const CATEGORY_HUES = [
    'bg-blue-500/10 text-blue-400', 'bg-violet-500/10 text-violet-400',
    'bg-amber-500/10 text-amber-400', 'bg-emerald-500/10 text-emerald-400',
];
function categoryHue(slug: string): string {
    let h = 0;
    for (let i = 0; i < slug.length; i++) h = (h + slug.charCodeAt(i)) % CATEGORY_HUES.length;
    return CATEGORY_HUES[h];
}

// Categories are free-text (admin-defined), so icons are picked by keyword
// match against common category names rather than a fixed enum — anything
// unmatched falls back to the generic help icon.
const CATEGORY_ICONS: Array<[RegExp, Component]> = [
    [/account|акаунт|потребител/i, UserCircle],
    [/server|сървър/i, Server],
    [/payment|donat|плащ|дарен/i, CreditCard],
    [/rule|правил/i, ShieldCheck],
    [/gift|подар/i, Gift],
    [/reward|award|награ/i, Trophy],
    [/discord|chat|чат/i, MessageCircle],
];
function categoryIcon(category: string): Component {
    return CATEGORY_ICONS.find(([re]) => re.test(category))?.[1] ?? CircleHelp;
}

function escapeHtml(s: string): string {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
// Highlights the search term inside a question. Question text is always
// plain (never markdown), so a straight escape-then-wrap is safe here —
// deliberately not applied to answer_html, since regex-splicing into
// already-rendered markdown risks breaking tags.
function highlightQuestion(text: string): string {
    const escaped = escapeHtml(text);
    const q = query.value.trim();
    if (!q) return escaped;

    const re = new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'ig');
    return escaped.replace(re, '<mark class="bg-amber-400/40 text-inherit rounded-sm">$1</mark>');
}

/** Up to 3 sibling entries from the same category, to keep a reader in the FAQ instead of bouncing. */
function relatedTo(group: Group, entry: Entry): Entry[] {
    return group.entries.filter((e) => e.id !== entry.id).slice(0, 3);
}

const totalCount = computed(() => props.groups.reduce((sum, g) => sum + g.entries.length, 0));

/** Search engines read this; costs nothing at runtime. */
const jsonLd = computed(() =>
    JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: props.groups.flatMap((g) => g.entries).map((e) => ({
            '@type': 'Question',
            name: e.question,
            acceptedAnswer: { '@type': 'Answer', text: e.answer_text },
        })),
    }),
);

// ── Search ───────────────────────────────────────────────────────
const VOTED_KEY = 'faq.voted';
const query = ref('');
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

// A "No" vote first opens an optional reason box instead of submitting right
// away — Send attaches the text, Skip (or submitVote directly for "Yes")
// submits without one.
const reasonOpenFor = ref<number | null>(null);
const reasonText = ref('');

function vote(entry: Entry, helpful: boolean) {
    if (voted.value.has(entry.id)) return;
    if (!helpful) {
        reasonOpenFor.value = entry.id;
        return;
    }
    submitVote(entry, true);
}
function submitReason(entry: Entry, send: boolean) {
    submitVote(entry, false, send ? reasonText.value.trim() : undefined);
}
function submitVote(entry: Entry, helpful: boolean, reason?: string) {
    voted.value.add(entry.id);
    saveVoted();
    reasonOpenFor.value = null;
    reasonText.value = '';
    router.post(route('faq.feedback', entry.id), { helpful, reason: reason || null }, { preserveScroll: true, preserveState: true });
}

// Debounced: logs a search term only once results settle on empty, not on
// every keystroke while still typing toward a match.
let missTimer: ReturnType<typeof setTimeout> | null = null;
watch(query, (q) => {
    if (missTimer) clearTimeout(missTimer);
    const term = q.trim();
    if (term.length < 2) return;

    missTimer = setTimeout(() => {
        if (query.value.trim() === term && !filteredGroups.value.length) {
            router.post(route('faq.search-miss'), { term }, { preserveScroll: true, preserveState: true });
        }
    }, 900);
});

async function copyLink(entry: Entry) {
    const url = `${window.location.origin}${window.location.pathname}#faq-${entry.slug}`;
    try {
        await navigator.clipboard.writeText(url);
        copiedId.value = entry.id;
        setTimeout(() => { if (copiedId.value === entry.id) copiedId.value = null; }, 1500);
    } catch {
        // Clipboard API unavailable — the link is still visible/selectable
        // via the address bar once the entry is opened.
    }
}

const filteredGroups = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return props.groups;

    return props.groups
        .map((group) => ({
            ...group,
            entries: group.entries.filter((e) =>
                e.question.toLowerCase().includes(q) || e.answer_text.toLowerCase().includes(q)),
        }))
        .filter((group) => group.entries.length > 0);
});
const visibleGroups = computed(() => (query.value.trim() ? filteredGroups.value : props.groups));

// Auto-open entries whose search match is only in the answer — otherwise the
// reason it matched stays hidden inside a collapsed <details>.
function openMatches() {
    const q = query.value.trim().toLowerCase();
    if (!q) return;
    nextTick(() => {
        for (const group of filteredGroups.value) {
            for (const entry of group.entries) {
                if (!entry.question.toLowerCase().includes(q)) {
                    const el = document.getElementById(`faq-${entry.slug}`) as HTMLDetailsElement | null;
                    if (el) el.open = true;
                }
            }
        }
    });
}

// ── Deep links: /faq#faq-<slug> or a Popular-question link opens + scrolls ──
function openAndScroll(slug: string) {
    const el = document.getElementById(`faq-${slug}`) as HTMLDetailsElement | null;
    if (!el) return;
    el.open = true;
    nextTick(() => el.scrollIntoView({ behavior: 'smooth', block: 'center' }));
}
function handleHash() {
    const match = window.location.hash.match(/^#faq-(.+)$/);
    if (!match) return;

    if (document.getElementById(`faq-${match[1]}`)) {
        openAndScroll(match[1]);
        return;
    }

    // Legacy links shared before the slug switch used the numeric id.
    const legacy = props.groups.flatMap((g) => g.entries).find((e) => String(e.id) === match[1]);
    if (legacy) openAndScroll(legacy.slug);
}

// ── Category scrollspy ──────────────────────────────────────────
const activeSlug = ref(props.groups[0]?.slug ?? '');
let observer: IntersectionObserver | null = null;

onMounted(() => {
    handleHash();
    window.addEventListener('hashchange', handleHash);

    observer = new IntersectionObserver(
        (observedEntries) => {
            const visible = observedEntries.filter((e) => e.isIntersecting);
            if (visible.length) activeSlug.value = (visible[0].target as HTMLElement).dataset.slug ?? activeSlug.value;
        },
        { rootMargin: '-120px 0px -70% 0px' },
    );
    document.querySelectorAll('[data-faq-section]').forEach((el) => observer?.observe(el));
});
onUnmounted(() => {
    window.removeEventListener('hashchange', handleHash);
    observer?.disconnect();
});
</script>

<template>
    <Head>
        <title>{{ t('ext.faq.title') }}</title>
        <meta name="description" :content="metaDescription" />
        <meta property="og:title" :content="t('ext.faq.title')" />
        <meta property="og:description" :content="metaDescription" />
        <meta property="og:type" content="website" />
        <component :is="'script'" type="application/ld+json">{{ jsonLd }}</component>
    </Head>
    <PublicLayout>
        <!-- Hero -->
        <section class="relative overflow-hidden border-b" :class="dark ? 'border-zinc-800/60 bg-[#0a0a0c]' : 'border-zinc-200 bg-gradient-to-b from-zinc-50 to-white'">
            <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
                <div class="hc-hero-glow absolute -top-32 -left-20 w-80 h-80 rounded-full blur-3xl opacity-25"
                    :style="{ background: 'radial-gradient(circle, #3b82f6, transparent 70%)' }" />
                <div class="hc-hero-glow hc-hero-glow--slow absolute -bottom-24 -right-16 w-72 h-72 rounded-full blur-3xl opacity-20"
                    :style="{ background: 'radial-gradient(circle, #8b5cf6, transparent 70%)' }" />
            </div>
            <div class="relative max-w-[1200px] mx-auto px-4 sm:px-6 pt-8 pb-10 sm:pb-12">
                <Breadcrumb class="hc-hero-in hc-hero-in--1 mb-5" :items="[{ label: 'Home', href: '/' }, { label: t('ext.faq.title') }]" />
                <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                    <div class="max-w-xl">
                        <div class="hc-hero-in hc-hero-in--1 inline-flex items-center gap-1.5 mb-3 px-2.5 py-1 rounded-full text-[11px] font-bold" :class="dark ? 'bg-blue-500/10 text-blue-400' : 'bg-blue-50 text-blue-600'">
                            <CircleHelp :size="12" :stroke-width="2.2" />
                            {{ t('ext.faq.eyebrow') }}
                        </div>
                        <h1 class="hc-hero-in hc-hero-in--2 text-3xl sm:text-4xl font-black tracking-tight mb-2.5" :class="textPri">{{ t('ext.faq.title') }}</h1>
                        <p class="hc-hero-in hc-hero-in--3 text-[15px] leading-relaxed" :class="textMute">{{ t('ext.faq.subtitle_count', { count: totalCount }) }}</p>
                    </div>

                    <div class="hc-hero-in hc-hero-in--3 relative w-full lg:w-80 shrink-0">
                        <label for="faq-search" class="sr-only">{{ t('ext.faq.search_label') }}</label>
                        <Search :size="15" :stroke-width="2" class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" :class="textMute" />
                        <input id="faq-search" v-model="query" type="search" :placeholder="t('ext.faq.search_placeholder')"
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                            :class="[panel, dark ? 'text-zinc-200 placeholder:text-zinc-600' : 'text-zinc-900 placeholder:text-zinc-400']"
                            @input="openMatches" />
                    </div>
                </div>
            </div>
        </section>

        <!-- Category jump nav — mobile only, horizontally scrollable. Desktop uses the sidebar below. -->
        <nav v-if="!query && groups.length > 1" aria-label="FAQ categories" class="lg:hidden sticky top-16 z-20 border-b backdrop-blur-sm" :class="navBg">
            <div class="max-w-[1200px] mx-auto px-4 sm:px-6 flex gap-2 py-2.5 overflow-x-auto">
                <a v-for="g in groups" :key="g.slug" :href="`#cat-${g.slug}`"
                    class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                    :class="activeSlug === g.slug
                        ? (dark ? 'bg-blue-500/15 text-blue-400' : 'bg-blue-50 text-blue-600')
                        : (dark ? 'text-zinc-500 hover:text-zinc-300 hover:bg-zinc-900' : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-100')">
                    <component :is="categoryIcon(g.category)" :size="12" :stroke-width="2.25" />
                    {{ g.category }} <span class="font-mono opacity-60">{{ g.entries.length }}</span>
                </a>
            </div>
        </nav>

        <div class="max-w-[1200px] mx-auto px-4 sm:px-6 py-8">
            <div class="lg:grid lg:grid-cols-[240px_1fr] lg:gap-8 lg:items-start">
                <!-- Category sidebar — desktop only, sticky under the site header -->
                <aside v-if="groups.length > 1" class="hidden lg:block">
                    <nav aria-label="FAQ categories" class="hc-reveal sticky top-20 rounded-xl border p-2" :class="panel">
                        <p class="px-2.5 pt-1.5 pb-2 text-[11px] font-bold uppercase tracking-wide" :class="textMute">{{ t('ext.faq.categories_title') }}</p>
                        <div class="space-y-0.5">
                            <a v-for="g in groups" :key="g.slug" :href="`#cat-${g.slug}`"
                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-[13px] font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                                :class="activeSlug === g.slug
                                    ? (dark ? 'bg-blue-500/15 text-blue-400' : 'bg-blue-50 text-blue-600')
                                    : (dark ? 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100')">
                                <component :is="categoryIcon(g.category)" :size="14" :stroke-width="2" class="shrink-0 opacity-80" />
                                <span class="truncate flex-1">{{ g.category }}</span>
                                <span class="text-[11px] font-mono opacity-60 shrink-0">{{ g.entries.length }}</span>
                            </a>
                        </div>
                    </nav>
                </aside>

                <!-- FAQ content -->
                <div class="min-w-0">
                    <!-- Popular — surfaces the same "most helpful" ranking as the homepage widget -->
                    <section v-if="popular.length && !query" aria-labelledby="popular-heading" class="mb-9">
                        <div class="flex items-center gap-2 mb-1">
                            <Flame :size="14" :stroke-width="2.2" class="text-amber-400" />
                            <h2 id="popular-heading" class="text-[13px] font-bold uppercase tracking-wide" :class="textPri">{{ t('ext.faq.popular_title') }}</h2>
                        </div>
                        <p class="text-[12px] mb-3" :class="textMute">{{ t('ext.faq.popular_subtitle') }}</p>
                        <div class="grid sm:grid-cols-3 gap-3">
                            <a v-for="p in popular" :key="p.id" :href="`#faq-${p.slug}`" class="rounded-xl border p-4 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                                :class="[panel, dark ? 'hover:border-zinc-700' : 'hover:border-zinc-300']"
                                @click.prevent="openAndScroll(p.slug)">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" :class="categoryHue(p.category)">{{ p.category }}</span>
                                <p class="text-[13px] font-semibold mt-2" :class="textPri">{{ p.question }}</p>
                            </a>
                        </div>
                    </section>

                    <div v-if="!totalCount" class="rounded-xl border px-5 py-14 text-center" :class="panel">
                        <CircleHelp :size="24" :stroke-width="1.5" class="mx-auto mb-2" :class="textMute" />
                        <p class="text-[13px]" :class="textMute">{{ t('ext.faq.no_entries') }}</p>
                    </div>
                    <div v-else-if="query && !filteredGroups.length" class="rounded-xl border px-5 py-14 text-center" :class="panel">
                        <Search :size="24" :stroke-width="1.5" class="mx-auto mb-2" :class="textMute" />
                        <p class="text-[13px]" :class="textMute">{{ t('ext.faq.no_results') }}</p>
                    </div>

                    <section v-for="group in visibleGroups" :key="group.slug" :id="`cat-${group.slug}`" :data-slug="group.slug" data-faq-section
                        :aria-labelledby="`cat-${group.slug}-h`" class="mb-9 last:mb-0 scroll-mt-24">
                        <h2 :id="`cat-${group.slug}-h`" class="flex items-center gap-2 text-[15px] font-bold mb-3" :class="textPri">
                            <component :is="categoryIcon(group.category)" :size="15" :stroke-width="2.25" class="opacity-70" />
                            {{ group.category }}
                            <span class="text-[11px] font-mono font-normal" :class="textMute">{{ group.entries.length }}</span>
                        </h2>
                        <div class="rounded-xl border divide-y overflow-hidden" :class="[panel, dark ? 'divide-zinc-800/50' : 'divide-zinc-100']">
                            <details v-for="entry in group.entries" :key="entry.id" :id="`faq-${entry.slug}`" class="group scroll-mt-24">
                                <summary class="list-none [&::-webkit-details-marker]:hidden cursor-pointer flex items-center gap-3 px-5 py-4 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 focus-visible:ring-inset"
                                    :class="dark ? 'hover:bg-white/[0.02]' : 'hover:bg-zinc-50'">
                                    <span class="flex-1 text-[14px] font-semibold" :class="textPri" v-html="highlightQuestion(entry.question)" />
                                    <ChevronDown :size="16" :stroke-width="2" aria-hidden="true" class="shrink-0 transition-transform group-open:rotate-180" :class="textMute" />
                                </summary>
                                <div class="px-5 pb-5">
                                    <div class="text-[13px] leading-relaxed [&_p]:mb-2.5 [&_p:last-child]:mb-0 [&_ul]:list-disc [&_ol]:list-decimal [&_ul]:pl-5 [&_ol]:pl-5 [&_li]:mb-1 [&_strong]:font-bold [&_code]:font-mono [&_code]:text-[12px] [&_code]:px-1 [&_code]:py-0.5 [&_code]:rounded"
                                        :class="dark
                                            ? 'text-zinc-400 [&_strong]:text-zinc-200 [&_a]:text-blue-400 [&_code]:bg-zinc-900 [&_code]:text-zinc-300'
                                            : 'text-zinc-600 [&_strong]:text-zinc-900 [&_a]:text-blue-600 [&_code]:bg-zinc-100 [&_code]:text-zinc-700'"
                                        v-html="entry.answer_html" />

                                    <div class="mt-4 pt-4 border-t" :class="dark ? 'border-zinc-800/50' : 'border-zinc-100'">
                                        <div class="flex items-center gap-4 flex-wrap">
                                            <div class="flex items-center gap-3">
                                                <span v-if="voted.has(entry.id)" class="flex items-center gap-1.5 text-[12px] text-emerald-400">
                                                    <Check :size="13" :stroke-width="2" /> {{ t('ext.faq.feedback_thanks') }}
                                                </span>
                                                <template v-else-if="reasonOpenFor !== entry.id">
                                                    <span class="text-[12px]" :class="textMute">{{ t('ext.faq.feedback_question') }}</span>
                                                    <button type="button" class="flex items-center gap-1 text-[12px] font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 rounded"
                                                        :class="dark ? 'text-zinc-400 hover:text-emerald-400' : 'text-zinc-500 hover:text-emerald-600'"
                                                        @click="vote(entry, true)">
                                                        <ThumbsUp :size="13" :stroke-width="2" /> {{ t('ext.faq.feedback_yes') }}
                                                    </button>
                                                    <button type="button" class="flex items-center gap-1 text-[12px] font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 rounded"
                                                        :class="dark ? 'text-zinc-400 hover:text-red-400' : 'text-zinc-500 hover:text-red-500'"
                                                        @click="vote(entry, false)">
                                                        <ThumbsDown :size="13" :stroke-width="2" /> {{ t('ext.faq.feedback_no') }}
                                                    </button>
                                                </template>
                                            </div>
                                            <button v-if="!voted.has(entry.id) && reasonOpenFor !== entry.id" type="button" class="ml-auto flex items-center gap-1 text-[12px] font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 rounded"
                                                :class="dark ? 'text-zinc-600 hover:text-zinc-300' : 'text-zinc-400 hover:text-zinc-600'"
                                                @click="copyLink(entry)">
                                                <Check v-if="copiedId === entry.id" :size="12" :stroke-width="2" class="text-emerald-400" />
                                                <Link2 v-else :size="12" :stroke-width="2" />
                                                {{ copiedId === entry.id ? t('ext.faq.link_copied') : t('ext.faq.copy_link') }}
                                            </button>
                                        </div>

                                        <div v-if="reasonOpenFor === entry.id" class="mt-2.5 flex items-center gap-2">
                                            <input v-model="reasonText" type="text" :placeholder="t('ext.faq.feedback_reason_placeholder')" maxlength="500"
                                                class="flex-1 px-3 py-1.5 rounded-lg border text-[12px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                                                :class="[panel, dark ? 'text-zinc-200 placeholder:text-zinc-600' : 'text-zinc-900 placeholder:text-zinc-400']"
                                                @keydown.enter="submitReason(entry, true)" />
                                            <button type="button" class="shrink-0 text-[12px] font-semibold text-blue-400 hover:text-blue-300 transition-colors" @click="submitReason(entry, true)">{{ t('ext.faq.feedback_send') }}</button>
                                            <button type="button" class="shrink-0 text-[12px] font-medium" :class="textMute" @click="submitReason(entry, false)">{{ t('ext.faq.feedback_skip') }}</button>
                                        </div>
                                    </div>

                                    <div v-if="relatedTo(group, entry).length" class="mt-4 pt-4 border-t flex flex-wrap items-center gap-2" :class="dark ? 'border-zinc-800/50' : 'border-zinc-100'">
                                        <span class="text-[11px] font-bold uppercase tracking-wide shrink-0" :class="textMute">{{ t('ext.faq.related_title') }}</span>
                                        <a v-for="r in relatedTo(group, entry)" :key="r.id" :href="`#faq-${r.slug}`"
                                            class="inline-flex items-center gap-1 text-[12px] font-semibold px-2.5 py-1 rounded-full transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                                            :class="dark ? 'bg-zinc-900 text-zinc-400 hover:text-zinc-200' : 'bg-zinc-100 text-zinc-600 hover:text-zinc-900'"
                                            @click.prevent="openAndScroll(r.slug)">
                                            {{ r.question }}
                                        </a>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </section>

                    <!-- Closing CTA — for questions the FAQ doesn't cover -->
                    <section v-if="totalCount && !query" class="hc-reveal rounded-xl border px-6 py-8 text-center" :class="panel">
                        <MessageCircle :size="22" :stroke-width="1.75" class="mx-auto mb-3" :class="textMute" />
                        <h2 class="text-[16px] font-bold mb-1.5" :class="textPri">{{ t('ext.faq.cta_title') }}</h2>
                        <p class="text-[13px] mb-4 max-w-sm mx-auto" :class="textMute">{{ t('ext.faq.cta_subtitle') }}</p>
                        <div class="flex justify-center">
                            <SocialLinks variant="bar" />
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
