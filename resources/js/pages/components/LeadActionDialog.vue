<script lang="ts" setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { ElButton, ElDialog, ElForm, ElFormItem, ElInput, ElMessage, ElSwitch, FormInstance, FormItemRule } from 'element-plus';

import type { Lead } from '@/types/leads';

const VALIDATION_RULES: Partial<Record<string, FormItemRule[]>> = {
    firstName: [{ required: true, message: 'Please provide a valid first name', trigger: 'blur' }],
    lastName: [{ required: true, message: 'Please provide a valid last name', trigger: 'blur' }],
    email: [
        { required: true, message: 'Please provide a valid email address', trigger: 'blur' },
        { required: true, type: 'email', message: 'Please provide a valid email address', trigger: 'blur' },
    ],
};

const initialFormState = {
    id: '',
    firstName: '',
    lastName: '',
    email: '',
    allowSendEmails: false,
};

const form = useForm<Lead>({ ...initialFormState });
const formRef = ref<FormInstance | null>(null);
const isDialogVisible = ref(false);
const isLoading = ref(false);
const formAction = ref('create');

const dialogTitle = computed(() => (formAction.value === 'create' ? 'Add New Lead' : 'Update Lead'));
const submitButtonText = computed(() => (formAction.value === 'create' ? 'Save' : 'Update'));

const resetForm = () => {
    Object.assign(form, initialFormState);
    formRef.value?.resetFields();
};

const handleDialogClose = () => {
    isDialogVisible.value = false;
    resetForm();
};

const handleApiError = (error: any, actionType: string) => {
    if (error && typeof error === 'object') {
        const errorMessages = Object.values(error).join('\n');
        ElMessage.error(errorMessages || `An error occurred while ${actionType} the lead`);
    } else {
        ElMessage.error(`An error occurred while ${actionType} the lead`);
    }
};

const submitForm = async () => {
    if (!formRef.value) return;

    try {
        const valid = await formRef.value.validate();
        if (!valid) return;

        isLoading.value = true;
        const isCreating = formAction.value === 'create';
        const actionType = isCreating ? 'adding' : 'editing';

        if (isCreating) {
            form.post(route('leads.store'), {
                errorBag: 'saveLead',
                preserveScroll: true,
                onSuccess: () => ElMessage.success('Lead added successfully'),
                onError: (errors) => handleApiError(errors, actionType),
            });
        } else {
            form.put(route('leads.update', form.id), {
                errorBag: 'updateLead',
                preserveScroll: true,
                onSuccess: () => ElMessage.success('Lead updated successfully'),
                onError: (errors) => handleApiError(errors, actionType),
            });
        }

        isDialogVisible.value = false;
    } catch (error) {
        handleApiError(error, formAction.value === 'create' ? 'adding' : 'editing');
    } finally {
        isLoading.value = false;
    }
};

const openDialog = (action: 'create' | 'update', lead?: Lead) => {
    formAction.value = action;
    resetForm();

    if (action === 'update' && lead) {
        Object.keys(initialFormState).forEach((key) => {
            if (key in lead) {
                // @ts-expect-error: suppress strict typing mismatch here (it's safe in this context)
                form[key] = lead[key];
            }
        });
    }

    isDialogVisible.value = true;
};

defineExpose({
    openDialog,
});
</script>

<template>
    <ElDialog v-model="isDialogVisible" :title="dialogTitle" width="500px" @close="handleDialogClose">
        <ElForm ref="formRef" :model="form" :rules="VALIDATION_RULES" label-position="top">
            <ElFormItem label="First Name" prop="firstName" required>
                <ElInput v-model="form.firstName" placeholder="ex. John" />
            </ElFormItem>

            <ElFormItem label="Last Name" prop="lastName" required>
                <ElInput v-model="form.lastName" placeholder="ex. Doe" />
            </ElFormItem>

            <ElFormItem label="Email" prop="email" required>
                <ElInput v-model="form.email" placeholder="ex. john@doe.com" />
            </ElFormItem>

            <ElFormItem label="Allow sending emails" prop="allowSendEmails">
                <ElSwitch v-model="form.allowSendEmails" />
                <span class="ml-2 text-gray-600">{{ form.allowSendEmails ? 'Yes' : 'No' }}</span>
            </ElFormItem>
        </ElForm>

        <template #footer>
            <span class="dialog-footer">
                <ElButton @click="handleDialogClose">Cancel</ElButton>
                <ElButton :loading="isLoading" type="primary" @click="submitForm">
                    {{ submitButtonText }}
                </ElButton>
            </span>
        </template>
    </ElDialog>
</template>
