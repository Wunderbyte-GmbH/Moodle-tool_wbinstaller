<template>
  <div class="error-container">
    <h3>
      {{store.state.strings.vueerrorheading}}
    </h3>
    <ul v-if="hasTargetKey" class="error-list">
      {{ errormsg.target.error }}
    </ul>
    <ul v-else-if="isObjectOrArray" class="error-list">
      <li v-for="(value, key) in errormsg" :key="key" class="error-item">
        <strong>{{ key }}:</strong> <span>{{ value }}</span>
      </li>
    </ul>
    <p v-else class="error-item">
      {{ errormsg }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useStore } from 'vuex'

const store = useStore()

const props = defineProps({
  errormsg: {
    type: Object,
    required: true,
  }
});

const isObjectOrArray = computed(() => {
  return typeof props.errormsg === 'object' && props.errormsg !== null;
});

const hasTargetKey = computed(() => {
  return props.errormsg && typeof props.errormsg === 'object' && 'target' in props.errormsg;
});

</script>

<style scoped>
.error-container {
  padding: 16px;
  border: 1px solid #f44336;
  background-color: #ffebee;
  color: #b71c1c;
  border-radius: 8px;
  margin: 20px auto;
  box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
}

h3 {
  margin-top: 0;
  color: #c62828;
  font-size: 1.5em;
  text-align: center;
}

.error-list {
  list-style-type: none;
  padding: 0;
  margin: 0;
}

.error-item {
  margin: 8px 0;
  padding: 4px 0;
  border-bottom: 1px solid #ef9a9a;
}

.error-item strong {
  color: #d32f2f;
}

.error-item span {
  color: #b71c1c;
}
</style>