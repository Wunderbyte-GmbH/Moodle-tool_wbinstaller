
 <template>
  <div>
    <div v-if="message.error">
      <h6>
        {{ store.state.strings.vueerror }}
      </h6>
      <div v-if="isString(message)">
          {{ message }}
      </div>
      <div v-else>
        <ul>
          <li v-for="error in message.error" :key="error" class="error-text">
            <span>
              {{ isFolded(error) ? error.slice(0, maxLength) + '...' : error }}
            </span>
            <button
              v-if="error.length > maxLength"
              @click="toggleFolded(error)"
              class="toggle-button"
            >
              {{ isFolded(error) ? store.state.strings.vueshowmore : store.state.strings.vueshowless }}
            </button>
          </li>
        </ul>
      </div>
    </div>
    <div v-if="message.warning">
      <h6>
        {{ store.state.strings.vuewarining }}
      </h6>
      <div v-if="isString(message)">
          {{ message }}
      </div>
      <div v-else>
        <ul>
          <li v-for="warning in message.warning" :key="warning" class="warning-text">
            {{ warning }}
          </li>
        </ul>
      </div>
    </div>
    <div v-if="message.success && ((!message.error && !message.warning) ||showlevel == 1)">
      <h6>
        {{ store.state.strings.vuesuccess }}
      </h6>
      <div v-if="isString(message)">
          {{ message }}
      </div>
      <div v-else>
        <ul>
          <li v-for="success in message.success" :key="success" class="success-text">
            {{ success }}
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useStore } from 'vuex'

const store = useStore()
// Define the props the component will receive
const props = defineProps({
  message: {
    type: Object,
    required: true,
    default: () => ({
      warning: [],
      error: []
    })
  },
  showlevel: {
    type: Number,
    required: false,
    default: () => (0)
  }
});

onMounted(() => {
  if (props.message.error) {
    props.message.error.forEach(error => {
      if (error.length > maxLength) {
        foldedErrors.value.add(error)
      }
    })
  }
})

const maxLength = 250  // Set the maximum length for foldable text
const foldedErrors = ref(new Set())

function isString(value) {
  return typeof value === 'string';
}

function toggleFolded(message) {
  if (foldedErrors.value.has(message)) {
    foldedErrors.value.delete(message)
  } else {
    foldedErrors.value.add(message)
  }
}

function isFolded(message) {
  return foldedErrors.value.has(message)
}
</script>

<style scoped>
.warning-text {
  color: darkorange;
  margin-left: 20px;
}

.error-text {
  color: red;
  font-weight: bold;
  margin-left: 20px;
}

.success-text {
  color: rgb(22, 193, 22);
  margin-left: 20px;
}

.toggle-button {
  background: none;
  border: none;
  color: blue;
  cursor: pointer;
  margin-left: 5px;
}
</style>