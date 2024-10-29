
 <template>
  <div>
    <div v-if="feedback.plugins">
      <h3>
        {{ store.state.strings.vuepluginfeedback }}
      </h3>
      <div v-if="feedback.plugins.needed">
        <ul class="list-group">
          <li class="list-group-item">
            <b>
              {{ store.state.strings.vuemandatoryplugin }}
              <button class="toggle-button" @click="toggleFold('plugin_mand')">
                <span :class="{ rotated: folded['plugin_mand'] }">▼</span>
              </button>
            </b>
            <transition name="fold">
              <ul v-show="!folded['plugin_mand']">
                <li v-for="(message, key) in feedback.plugins.needed" :key="key" style="margin-left: 20px; list-style-type: disc;">
                  <h4 style="text-decoration: underline;">
                    {{ key }}
                  </h4>

                    <PluginFeedback v-show="!folded[key]" :message/>
                </li>
              </ul>
            </transition>
          </li>
        </ul>
      </div>
      <div v-if="feedback.plugins.optional">
        <ul class="list-group">
          <li class="list-group-item">
            <b>
              {{ store.state.strings.vueoptionalplugin }}
              <button class="toggle-button" @click="toggleFold('plugin_opit')">
                <span :class="{ rotated: folded['plugin_opit'] }">▼</span>
              </button>
            </b>
            <transition name="fold">
              <ul v-show="!folded['plugin_opit']">
                <li
                  v-for="(message, key) in feedback.plugins.optional"
                  :key="key"
                  style="margin-left: 20px; list-style-type: none;"
                >
                  <div style="display: flex;">
                    <input
                      type="checkbox"
                      v-model="checkedOptionalPlugins"
                      :value="key"
                      class="check-margin"
                    />
                    <h4 style="text-decoration: underline;">
                      {{ key }}
                    </h4>
                  </div>
                  <transition name="fold">
                    <PluginFeedback v-show="!folded[key]" :message/>
                  </transition>
                </li>
              </ul>
            </transition>
          </li>
        </ul>
      </div>
    </div>
    <div v-for="(feedbackparts, index) in feedback" :key="index">
      <div v-if="index!='plugins'">
        <h3>
          {{ store.state.strings['vue' + index + 'heading'] }}
        </h3>
        <ul class="list-group">
          <li class="list-group-item" v-for="(message, key) in feedbackparts.needed" :key="key">
            <h4
              style="text-decoration: underline;"
            >
              {{ key }}
              <button class="toggle-button" @click="toggleFold(key)">
                <span :class="{ rotated: folded[key] }">▼</span>
              </button>
            </h4>
            <transition name="fold">
              <PluginFeedback v-show="!folded[key]" :message/>
            </transition>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
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

const folded = ref({});

const toggleFold = (key) => {
  folded.value[key] = !folded.value[key];
};

</script>

<style scoped>
.check-margin {
  margin-bottom: 0.25rem;
  margin-right: 0.5rem;
}

.toggle-button {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.2rem;
  transition: transform 0.3s ease;
}

.toggle-button span {
  display: inline-block;
  transition: transform 0.3s ease;
}

.toggle-button span.rotated {
  transform: rotate(180deg);
}

.fold-enter-active, .fold-leave-active {
  transition: max-height 0.3s ease, opacity 0.3s ease;
}
.fold-enter-from, .fold-leave-to {
  max-height: 0;
  opacity: 0;
}
.fold-enter-to, .fold-leave-from  {
  max-height: 500px;
  opacity: 1;
}

.list-group-item b {
  cursor: pointer;
  display: inline-flex;
  align-items: center;
}
.list-group-item b span {
  margin-left: 8px;
  font-size: 0.8em;
}
</style>