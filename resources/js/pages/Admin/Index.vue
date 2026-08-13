<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { CircleHelp, Plus, Pencil, Trash2, X, ThumbsUp, ThumbsDown, Search, Link2, Eye, EyeOff } from '@lucide/vue';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import PageHeader from '@/components/UI/PageHeader.vue';
import MarkdownEditor from '@/components/Admin/MarkdownEditor.vue';
import { useLocale } from '@/composables/useLocale';

interface Entry {
    id: number; category: string | null; question: string; slug: string; answer: string;
    is_published: boolean; sort: number; helpful_count: number; not_helpful_count: number;
}
interface Group { category: string; entries: Entry[] }

const props = defineProps<{ groups: Group[]; categories: string[] }>();
const { t } = useLocale();

function slugify(value: string): string {
    return value.toLowerCase().trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

const filter = ref('');
const filteredGroups = computed(() => {
    const q = filter.value.trim().toLowerCase();
    if (!q) return props.groups;

    return props.groups
        .map((group) => ({ ...group, entries: group.entries.filter((e) => e.question.toLowerCase().includes(q)) }))
        .filter((group) => group.entries.length > 0);
});

const editing = ref<Entry | null>(null);
const showForm = ref(false);
const slugTouched = ref(false);

const form = useForm({
    category: '', question: '', slug: '', answer: '', is_published: true, sort: 0,
});

function openCreate() {
    editing.value = null;
    slugTouched.value = false;
    form.reset();
    form.clearErrors();
    showForm.value = true;
}
function openEdit(e: Entry) {
    editing.value = e;
    slugTouched.value = true;
    form.clearErrors();
    form.category = e.category ?? '';
    form.question = e.question;
    form.slug = e.slug;
    form.answer = e.answer;
    form.is_published = e.is_published;
    form.sort = e.sort;
    showForm.value = true;
}
function onQuestionInput() {
    if (!slugTouched.value) form.slug = slugify(form.question);
}
function submit() {
    if (editing.value) {
        form.put(route('admin.faq.update', editing.value.id), { onSuccess: () => (showForm.value = false) });
    } else {
        form.post(route('admin.faq.store'), { onSuccess: () => (showForm.value = false) });
    }
}
function destroy(e: Entry) {
    if (!confirm(t('ext.faq.confirm_delete'))) return;
    router.delete(route('admin.faq.destroy', e.id), { preserveScroll: true });
}

// ── Bulk selection ──────────────────────────────────────────────
const selected = ref<Set<number>>(new Set());

function toggleSelected(id: number) {
    const next = new Set(selected.value);
    next.has(id) ? next.delete(id) : next.add(id);
    selected.value = next;
}
function clearSelection() {
    selected.value = new Set();
}
function bulk(action: 'publish' | 'unpublish' | 'delete') {
    if (action === 'delete' && !confirm(t('ext.faq.bulk_confirm_delete', { count: selected.value.size }))) return;

    router.post(route('admin.faq.bulk'), { action, ids: [...selected.value] }, {
        preserveScroll: true,
        onSuccess: clearSelection,
    });
}

const input = 'w-full bg-zinc-900/60 border border-zinc-800/70 text-zinc-100 rounded-lg px-3 py-2 text-sm placeholder:text-zinc-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20';
const label = 'text-zinc-400 text-[11px] font-bold uppercase tracking-wider mb-1.5 block';
</script>

<template>
    <Head :title="t('ext.faq.admin_title')" />
    <AdminLayout :title="t('ext.faq.admin_title')">
        <div class="flex items-start justify-between gap-4 mb-5">
            <PageHeader :title="t('ext.faq.admin_title')" :description="t('ext.faq.admin_subtitle')" :icon="CircleHelp" />
            <button type="button" class="shrink-0 flex items-center gap-1.5 bg-blue-500 text-white font-semibold rounded-lg px-3 py-2 text-xs hover:bg-blue-400 transition-colors" @click="openCreate">
                <Plus :size="13" :stroke-width="2" /> {{ t('ext.faq.add_entry') }}
            </button>
        </div>

        <div v-if="groups.length" class="flex items-center gap-3 mb-4 flex-wrap">
            <div class="relative max-w-sm flex-1 min-w-[200px]">
                <Search :size="14" :stroke-width="2" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-600 pointer-events-none" />
                <input v-model="filter" type="search" :placeholder="t('ext.faq.filter_placeholder')" class="w-full pl-9 pr-3 py-2 rounded-lg bg-[#111113] border border-zinc-800/70 text-zinc-200 text-sm placeholder:text-zinc-600 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20" />
            </div>

            <div v-if="selected.size" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-500/10 border border-blue-500/20">
                <span class="text-[12px] font-bold text-blue-400">{{ t('ext.faq.selected_count', { count: selected.size }) }}</span>
                <button type="button" class="flex items-center gap-1 text-[11px] font-semibold text-zinc-400 hover:text-emerald-400 transition-colors" @click="bulk('publish')">
                    <Eye :size="12" :stroke-width="2" /> {{ t('ext.faq.bulk_publish') }}
                </button>
                <button type="button" class="flex items-center gap-1 text-[11px] font-semibold text-zinc-400 hover:text-amber-400 transition-colors" @click="bulk('unpublish')">
                    <EyeOff :size="12" :stroke-width="2" /> {{ t('ext.faq.bulk_unpublish') }}
                </button>
                <button type="button" class="flex items-center gap-1 text-[11px] font-semibold text-zinc-400 hover:text-red-400 transition-colors" @click="bulk('delete')">
                    <Trash2 :size="12" :stroke-width="2" /> {{ t('ext.faq.bulk_delete') }}
                </button>
                <button type="button" class="text-zinc-600 hover:text-zinc-300 ml-1" @click="clearSelection">
                    <X :size="14" :stroke-width="2" />
                </button>
            </div>
        </div>

        <div v-if="!groups.length" class="bg-[#111113] border border-zinc-800/70 rounded-xl px-5 py-12 text-center">
            <CircleHelp :size="24" :stroke-width="1.5" class="mx-auto text-zinc-700 mb-2" />
            <p class="text-[13px] text-zinc-600">{{ t('ext.faq.no_entries_admin') }}</p>
        </div>
        <div v-else-if="!filteredGroups.length" class="bg-[#111113] border border-zinc-800/70 rounded-xl px-5 py-12 text-center">
            <Search :size="24" :stroke-width="1.5" class="mx-auto text-zinc-700 mb-2" />
            <p class="text-[13px] text-zinc-600">{{ t('ext.faq.no_results') }}</p>
        </div>

        <div v-for="group in filteredGroups" :key="group.category" class="mb-5 last:mb-0">
            <div class="flex items-center gap-2 mb-2 px-1">
                <h2 class="text-[12px] font-bold uppercase tracking-wide text-zinc-500">{{ group.category }}</h2>
                <span class="text-[11px] font-mono text-zinc-700">{{ group.entries.length }}</span>
            </div>
            <div class="bg-[#111113] border border-zinc-800/70 rounded-xl overflow-hidden">
                <div v-for="e in group.entries" :key="e.id" class="px-5 py-3.5 border-b border-zinc-800/50 last:border-0 flex items-center gap-4">
                    <input type="checkbox" :checked="selected.has(e.id)" class="w-4 h-4 rounded border-zinc-700 bg-zinc-900 accent-blue-500 shrink-0" @change="toggleSelected(e.id)" />
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-[14px] font-bold text-zinc-100 truncate">{{ e.question }}</p>
                            <span v-if="!e.is_published" class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-400 shrink-0">{{ t('ext.faq.draft') }}</span>
                        </div>
                        <div class="flex items-center gap-3 mt-1 text-[11px] text-zinc-600">
                            <span class="flex items-center gap-1 font-mono"><Link2 :size="11" :stroke-width="2" /> {{ e.slug }}</span>
                            <span class="flex items-center gap-1"><ThumbsUp :size="11" :stroke-width="2" /> {{ e.helpful_count }}</span>
                            <span class="flex items-center gap-1"><ThumbsDown :size="11" :stroke-width="2" /> {{ e.not_helpful_count }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" :title="t('ext.faq.edit_entry')" class="w-7 h-7 flex items-center justify-center rounded-lg text-zinc-500 hover:text-zinc-100 hover:bg-zinc-800 transition-colors" @click="openEdit(e)">
                            <Pencil :size="13" :stroke-width="1.75" />
                        </button>
                        <button type="button" class="w-7 h-7 flex items-center justify-center rounded-lg text-zinc-500 hover:text-red-400 hover:bg-red-500/10 transition-colors" @click="destroy(e)">
                            <Trash2 :size="13" :stroke-width="1.75" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / edit modal -->
        <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-8 overflow-y-auto" @mousedown.self="showForm = false">
            <div class="w-full max-w-xl bg-[#111113] border border-zinc-800 rounded-2xl p-6 my-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-zinc-100 text-base font-bold">{{ editing ? t('ext.faq.edit_entry') : t('ext.faq.add_entry') }}</h3>
                    <button type="button" class="text-zinc-600 hover:text-zinc-300" @click="showForm = false"><X :size="16" /></button>
                </div>
                <form class="flex flex-col gap-3" @submit.prevent="submit">
                    <div class="grid grid-cols-[1fr_120px] gap-3">
                        <div>
                            <label :class="label">{{ t('ext.faq.field_question') }}</label>
                            <input v-model="form.question" type="text" :class="input" @input="onQuestionInput" />
                            <p v-if="form.errors.question" class="text-red-400 text-xs mt-1">{{ form.errors.question }}</p>
                        </div>
                        <div>
                            <label :class="label">{{ t('ext.faq.field_sort') }}</label>
                            <input v-model.number="form.sort" type="number" min="0" :class="input" />
                        </div>
                    </div>
                    <div>
                        <label :class="label">{{ t('ext.faq.field_slug') }}</label>
                        <input v-model="form.slug" type="text" :class="[input, 'font-mono']" placeholder="how-do-i-reset-my-password" @input="slugTouched = true" />
                        <p class="text-zinc-600 text-[11px] mt-1">{{ t('ext.faq.field_slug_hint') }}</p>
                        <p v-if="form.errors.slug" class="text-red-400 text-xs mt-1">{{ form.errors.slug }}</p>
                    </div>
                    <div>
                        <label :class="label">{{ t('ext.faq.field_category') }}</label>
                        <input v-model="form.category" type="text" list="faq-categories" :class="input" :placeholder="t('ext.faq.field_category_placeholder')" />
                        <datalist id="faq-categories">
                            <option v-for="c in categories" :key="c" :value="c" />
                        </datalist>
                    </div>
                    <div>
                        <label :class="label">{{ t('ext.faq.field_answer') }}</label>
                        <MarkdownEditor v-model="form.answer" />
                        <p v-if="form.errors.answer" class="text-red-400 text-xs mt-1">{{ form.errors.answer }}</p>
                    </div>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input v-model="form.is_published" type="checkbox" class="w-4 h-4 rounded border-zinc-700 bg-zinc-900 accent-blue-500" />
                        <span class="text-zinc-300 text-sm">{{ t('ext.faq.field_published') }}</span>
                    </label>
                    <button type="submit" :disabled="form.processing" class="mt-2 bg-blue-500 text-white font-semibold rounded-lg px-4 py-2 text-sm hover:bg-blue-400 transition-colors disabled:opacity-50 w-fit">
                        {{ editing ? t('ext.faq.edit_entry') : t('ext.faq.add_entry') }}
                    </button>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
