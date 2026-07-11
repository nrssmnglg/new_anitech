<script setup>
defineProps({
    id: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    modelValue: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'text',
    },
    rows: {
        type: Number,
        default: 4,
    },
    error: {
        type: String,
        default: '',
    },
    textarea: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="farmer-app__field">
        <label :for="id">{{ label }}</label>
        <textarea
            v-if="textarea"
            :id="id"
            :rows="rows"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <input
            v-else
            :id="id"
            :type="type"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <small v-if="error" class="farmer-app__error">{{ error }}</small>
    </div>
</template>
