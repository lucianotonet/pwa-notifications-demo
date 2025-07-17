<template>
    <div class="max-w-md mx-auto mt-10 p-6 bg-white rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6">Envio Manual de Notificação Push</h1>
        <form @submit.prevent="sendNotification">
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700">Título</label>
                <input v-model="title" id="title" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            </div>
            <div class="mb-4">
                <label for="body" class="block text-sm font-medium text-gray-700">Mensagem</label>
                <textarea v-model="body" id="body" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required></textarea>
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">Enviar Notificação</button>
        </form>
        <p v-if="$page.props.flash && $page.props.flash.success" class="mt-4 text-center text-green-600">{{ $page.props.flash.success }}</p>
        <p v-if="error" class="mt-4 text-center text-red-600">{{ error }}</p>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const title = ref('');
const body = ref('');
const error = ref('');

function sendNotification() {
    error.value = '';

    router.post('/notifications/send', { title: title.value, body: body.value }, {
        onSuccess: () => {
            title.value = '';
            body.value = '';
        },
        onError: (errors) => {
            error.value = Object.values(errors).join(', ');
        },
    });
}
</script> 