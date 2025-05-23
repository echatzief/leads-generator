<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import { ElButton, ElTable, ElTableColumn } from 'element-plus';

import type { Lead } from '@/types/leads';

const props = defineProps<{
    leads: Lead[];
    total: number;
    totalPages: number;
    page: number;
    limit: number;
    handleEdit: (lead: Lead) => void;
    handleDelete: (lead: Lead) => void;
}>();

const currentPage = ref(props.page);
const currentLimit = ref(props.limit);

const handleCurrentChange = (newPage: number) => {
    currentPage.value = newPage;
    router.get('/', {
        page: newPage,
        limit: currentLimit.value,
    });
};

const handleLimitChange = (newLimit: number) => {
    currentLimit.value = newLimit;
    router.get('/', {
        page: currentPage.value,
        limit: newLimit,
    });
};
</script>

<template>
    <div class="rounded bg-white shadow">
        <div class="rounded bg-white shadow">
            <ElTable :data="leads" style="width: 100%">
                <ElTableColumn label="ID" prop="id" />
                <ElTableColumn label="First Name" prop="firstName" />
                <ElTableColumn label="Last Name" prop="lastName" />
                <ElTableColumn label="Email" prop="email" />
                <ElTableColumn label="Allow Send Emails" prop="allowSendEmails">
                    <template #default="scope">
                        <span v-if="scope.row.allowSendEmails" class="text-green-600">Yes</span>
                        <span v-else class="text-red-600">No</span>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="Actions" width="150">
                    <template #default="scope">
                        <ElButton size="small" type="primary" @click="handleEdit(scope.row)">Edit</ElButton>
                        <ElButton size="small" type="danger" @click="handleDelete(scope.row)">Delete</ElButton>
                    </template>
                </ElTableColumn>

                <template #empty>
                    <ElEmpty description="No leads data available" />
                </template>
            </ElTable>

            <div class="flex justify-end px-6 py-4">
                <ElPagination
                    :current-page="currentPage"
                    :page-size="currentLimit ?? 10"
                    :total="total ?? 0"
                    background
                    layout="total, sizes, prev, pager, next"
                    @current-change="handleCurrentChange"
                    @size-change="handleLimitChange"
                />
            </div>
        </div>
    </div>
</template>
