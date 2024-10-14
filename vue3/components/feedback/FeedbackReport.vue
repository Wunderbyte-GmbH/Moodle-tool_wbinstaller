
 <template>
  <div>
    <h2>
      {{ store.state.strings.vueinstallbtn }}
    </h2>
    <div v-for="(messages, type) in feedback" :key="type" style="margin-left: 20px;">
      <h3>{{type}}</h3>
      <div v-if="isString(messages)">
        {{messages}}
      </div>
      <div v-else>
        <ul v-for="(messagestype, key) in messages" :key="key" class="list-group">
          <li v-for="(message, key) in messagestype" :key="key" style="margin-left: 20px; list-style-type: disc;">
            <h4 style="text-decoration: underline;">
              {{ key }}
            </h4>
            <PluginFeedback
              :message
              :showlevel = "type=='config' ? 1 : 0"
            />
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import PluginFeedback from './PluginFeedback.vue';
import { useStore } from 'vuex'

const store = useStore()
// Define the props the component will receive
const props = defineProps({
  feedback: {
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