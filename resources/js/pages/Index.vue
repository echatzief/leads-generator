<script lang="ts" setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import { ElButton, ElMessage, ElMessageBox } from 'element-plus';

import LeadActionDialog from '@/pages/components/LeadActionDialog.vue';
import LeadsTable from '@/pages/components/LeadsTable.vue';

import type { Lead } from '@/types/leads';

defineProps<{
    leads: Lead[];
    total: number;
    totalPages: number;
    page: number;
    limit: number;
}>();

const leadActionDialog = ref(null);

const openLeadActionDialog = (action = 'create', lead?: Lead) => {
    if (leadActionDialog.value) {
        if (lead) {
            const { id, firstName, lastName, email, allowSendEmails } = lead;

            // @ts-expect-error: suppress strict typing mismatch here (it's safe in this context)
            leadActionDialog.value.openDialog(action, {
                id,
                firstName,
                lastName,
                email,
                allowSendEmails,
            });
        } else {
            // @ts-expect-error: suppress strict typing mismatch here (it's safe in this context)
            leadActionDialog.value.openDialog(action, {
                id: '',
                firstName: '',
                lastName: '',
                email: '',
                allowSendEmails: false,
            });
        }
    }
};

const handleDelete = (lead: Lead) => {
    ElMessageBox.confirm('Are you sure you want to delete this lead?', 'Warning', {
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel',
        type: 'warning',
    })
        .then(() => {
            const form = useForm({});
            form.delete(route('leads.destroy', lead.id), {
                errorBag: 'deleteLead',
                preserveScroll: true,
                onSuccess: () => {
                    ElMessage.success('Lead deleted successfully');
                },
                onError: (error) => {
                    const errorKeys = Object.keys(error);
                    const message =
                        errorKeys.length > 0 ? errorKeys.map((key) => error[key]).join('\n') : 'An error occurred while deleting the lead';
                    ElMessage.error(message);
                },
            });
        })
        .catch(() => {});
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col space-y-6 p-6">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Leads</h1>
            <div>
                <ElButton type="primary" @click="() => openLeadActionDialog('create')">Add Lead</ElButton>
            </div>
        </div>

        <LeadsTable
            :handle-delete="handleDelete"
            :handle-edit="(lead) => openLeadActionDialog('update', lead)"
            :leads="leads"
            :limit="limit"
            :page="page"
            :total="total"
            :total-pages="totalPages"
        />

        <LeadActionDialog ref="leadActionDialog" />
    </div>
</template>
