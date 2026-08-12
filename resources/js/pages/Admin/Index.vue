<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { CircleHelp, Plus, Pencil, Trash2, X, ThumbsUp, ThumbsDown } from '@lucide/vue';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import PageHeader from '@/components/UI/PageHeader.vue';
import MarkdownEditor from '@/components/Admin/MarkdownEditor.vue';
import { useLocale } from '@/composables/useLocale';

interface Entry {
    id: number; category: string | null; question: string; answer: string;
    is_published: boolean; sort: number; helpful_count: number; not_helpful_count: number;
}

defineProps<{ entries: Entry[]; categories: string[] }>();
const { t } = useLocale();

const editing = ref<Entry | null>(null);
const showForm = ref(false);

const form = useForm({
    category: '', question: '', answer: '', is_published: true, sort: 0,
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showForm.value = true;
}
function openEdit(e: Entry) {
    editing.value = e;
    form.clearErrors();
    form.category = e.category ?? '';
    form.question = e.question;
    form.answer = e.answer;
    form.is_published = e.is_published;
    form.sort = e.sort;
    showForm.value = true;
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

        <div class="bg-[#111113] border border-zinc-800/70 rounded-xl overflow-hidden">
            <div v-if="!entries.length" class="px-5 py-12 text-center">
                <CircleHelp :size="24" :stroke-width="1.5" class="mx-auto text-zinc-700 mb-2" />
                <p class="text-[13px] text-zinc-600">{{ t('ext.faq.no_entries_admin') }}</p>
            </div>

            <div v-for="e in entries" :key="e.id" class="px-5 py-3.5 border-b border-zinc-800/50 last:border-0 flex items-center gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="text-[14px] font-bold text-zinc-100 truncate">{{ e.question }}</p>
                        <span v-if="!e.is_published" class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-400 shrink-0">{{ t('ext.faq.draft') }}</span>
                        <span v-if="e.category" class="text-[10px] font-mono text-zinc-600 shrink-0">{{ e.category }}</span>
                    </div>
                    <div class="flex items-center gap-3 mt-1 text-[11px] text-zinc-600">
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
                            <input v-model="form.question" type="text" :class="input" />
                            <p v-if="form.errors.question" class="text-red-400 text-xs mt-1">{{ form.errors.question }}</p>
                        </div>
                        <div>
                            <label :class="label">{{ t('ext.faq.field_sort') }}</label>
                            <input v-model.number="form.sort" type="number" min="0" :class="input" />
                        </div>
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
