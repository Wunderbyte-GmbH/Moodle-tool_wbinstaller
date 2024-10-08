
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
            {{ error }}
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
    <div v-if="message.success && !message.error && !message.warning">
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
  }
});

function isString(value) {
  return typeof value === 'string';
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
</style>