 <template>
  <div :class="{ 'loading-cursor': isInstalling }" class="container mt-4">
    <div v-if="nextstep && !finished.status">
      <p>{{ store.state.strings.vuenextstep }}</p>
        <button
          v-if="nextstep"
          class="btn btn-primary mt-4"
          @click="installRecipe"
          :disabled="isInstalling"
        >
          {{ store.state.strings.vuenextstepbtn }}
        </button>
    </div>
    <div  v-if="finished.status">
      <p>{{ store.state.strings.vuefinishedrecipe }}</p>
    </div>
    <StepCounter :finished/>
    <div class="form-group">
      <label for="zipFileUpload">
        {{ store.state.strings.vuechooserecipe }}
      </label>
      <input
        type="file"
        class="form-control-file"
        id="zipFileUpload"
        @change="handleFileUpload"
        accept=".zip"
        ref="fileInput"
        :disabled="nextstep"
      />
    </div>
    <transition name="fade">
      <div v-if="isInstalling" class="waiting-screen mt-4">
        <div class="spinner"></div>
        <p>
          {{ store.state.strings.vuewaitingtext }}
        </p>
        <ProgressTracking :uploadedFileName/>
      </div>
    </transition>
    <transition name="fade">
      <div v-if="uploadedFileName && Object.values(feedback).length > 0" class="mt-4">
        <div v-if="feedback.plugins">
          <h3>
            {{ store.state.strings.vuepluginfeedback }}
          </h3>
          <div v-if="feedback.plugins.needed">
            <ul class="list-group">
              <li class="list-group-item">
                <b>
                  {{ store.state.strings.vuemandatoryplugin }}
                </b>
                <ul>
                  <li v-for="(message, key) in feedback.plugins.needed" :key="key" style="margin-left: 20px; list-style-type: disc;">
                    <h4 style="text-decoration: underline;">
                      {{ key }}
                    </h4>
                    <PluginFeedback :message/>
                  </li>
                </ul>
              </li>
            </ul>
          </div>
          <div v-if="feedback.plugins.optional">
            <ul class="list-group">
              <li class="list-group-item">
                <b>
                  {{ store.state.strings.vueoptionalplugin }}
                </b>
                <ul>
                  <li v-for="(message, key) in feedback.plugins.optional" :key="key" style="margin-left: 20px; list-style-type: none;">
                    <input type="checkbox" v-model="checkedOptionalPlugins" :value="key" />
                    <h4 style="text-decoration: underline;">
                      {{ key }}
                    </h4>
                    <PluginFeedback :message/>
                  </li>
                </ul>
              </li>
            </ul>
          </div>
        </div>
        <div v-for="(feedbackparts, index) in feedback" :key="index">
            <div v-if="index!='plugins'">
              <h3>
                {{ store.state.strings['vue'+index+'heading'] }}
              </h3>
              <ul class="list-group">
                <li class="list-group-item" v-for="(message, key) in feedbackparts.needed" :key="key">
                  <h4 style="text-decoration: underline;">
                    {{ key }}
                  </h4>
                  <PluginFeedback :message/>
                </li>
              </ul>
            </div>
        </div>
        <div v-if="feedback.customfields" class="mt-4">
          <h3>
            {{ store.state.strings.vuecustomfieldzip }}
          </h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="(message, key) in feedback.customfields.needed" :key="key">
              {{ store.state.strings.vuecategories }}
              <h4 style="text-decoration: underline;">
                {{ key }}
              </h4>
              <PluginFeedback :message/>
            </li>
          </ul>
        </div>
        <div v-if="feedback.simulations" class="mt-4">
          <h3>
            {{ store.state.strings.vuesimulationzip }}
          </h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="(message, key) in feedback.simulations.needed" :key="key">
              <h4 style="text-decoration: underline;">
                {{ key }}
              </h4>
              <PluginFeedback :message/>
            </li>
          </ul>
        </div>
        <div v-if="feedback.questions" class="mt-4">
          <h3>
            {{ store.state.strings.vuequestionszip }}
          </h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="(message, key) in feedback.questions.needed" :key="key">
              <h4 style="text-decoration: underline;">
                {{ key }}
              </h4>
              <PluginFeedback :message/>
            </li>
          </ul>
        </div>
        <div v-if="feedback.config" class="mt-4">
          <h3>
            {{ store.state.strings.vueconfigzip }}
          </h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="(message, key) in feedback.config.needed" :key="key">
              <h4 style="text-decoration: underline;">
                {{ key }}
              </h4>
              <PluginFeedback
                :message
                showlevel = 1
              />
            </li>
          </ul>
        </div>
        <button
          v-if="!nextstep"
          class="btn btn-primary mt-4"
          @click="installRecipe"
          :disabled="isInstalling"
        >
          {{ store.state.strings.vueinstallbtn }}
        </button>
      </div>
    </transition>
    <transition name="fade">
      <div v-if="!uploadedFileName && Object.values(feedback).length > 0" class="mt-4">
        <FeedbackReport :feedback/>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useStore } from 'vuex';
import { notify } from "@kyvg/vue3-notification"
import PluginFeedback from '../feedback/PluginFeedback.vue';
import FeedbackReport from '../feedback/FeedbackReport.vue';
import ProgressTracking from '../feedback/ProgressTracking.vue';
import StepCounter from '../feedback/StepCounter.vue'

// Reactive state for the list of links and courses
const store = useStore();
const feedback = ref([]);
const finished = ref(false);
const checkedOptionalPlugins = ref([]);
let uploadedFile = null;
let uploadedFileName = ref('');
const fileInput = ref(null);
let nextstep = ref(false);

const isInstalling = ref(false);
const totalProgress = ref(0);
const taskProgress = ref(0);

const installRecipe = async () => {
  if (uploadedFile) {
    feedback.value = []
    isInstalling.value = true;
    totalProgress.value = 0;
    taskProgress.value = 0;
    try {
      const base64File = await convertFileToBase64(uploadedFile);
      const selectedPlugins = JSON.stringify(checkedOptionalPlugins.value);
      const response = await store.dispatch('installRecipe',
        {
          uploadedFile: base64File,
          filename: uploadedFileName.value,
          selectedOptionalPlugins: selectedPlugins
        }
      );
      feedback.value = JSON.parse(response.feedback)
      finished.value = JSON.parse(response.finished)
      if (!finished.value.status) {
        nextstep.value  = true
      }

      if (feedback.value.status == 0) {
        notify({
          title: store.state.strings.success,
          text: store.state.strings.success_description,
          type: 'success'
        });
      } else if (feedback.value.status == 1) {
        notify({
          title: store.state.strings.warning,
          text: store.state.strings.warning_description,
          type: 'warn'
        });
      } else if (feedback.value.status == 2) {
        notify({
          title: store.state.strings.error,
          text: store.state.strings.error_description,
          type: 'error'
        });
      }
    } catch (error) {
      notify({
        title: store.state.strings.error,
        text: store.state.strings.error_description,
        type: 'error'
      });
    }  finally {
      if (finished.value.status) {
        nextstep.value  = false
        uploadedFile.value = null
        uploadedFileName.value = ''
        if (fileInput.value) {
          fileInput.value.value = '';
        }
      }
      isInstalling.value = false
    }
  }
};

const convertFileToBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = (error) => reject(error);
    reader.readAsDataURL(file);
  });
};

// Function to handle file upload
const handleFileUpload = async (event) => {
  feedback.value = []
  isInstalling.value = true;
  uploadedFile = event.target.files[0];
  if (uploadedFile && uploadedFile.name.endsWith('.zip')) {
    uploadedFileName.value = uploadedFile.name;
    console.log('uploadedFile')
    console.log(uploadedFile)
    const base64File = await convertFileToBase64(uploadedFile);
    const response = await store.dispatch('checkRecipe',
      {
        uploadedFile: base64File,
        filename: uploadedFileName.value,
      }
    );
    const responseparsed = JSON.parse(response.feedback)
    feedback.value = responseparsed.feedback
    finished.value = responseparsed.finished
  } else {
    uploadedFileName.value = '';
  }
  isInstalling.value = false;
};

</script>

<style scoped>
.waiting-screen {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.spinner {
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-left-color: #3498db;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.waiting-screen p {
  margin-top: 1rem;
  font-size: 1.2rem;
  color: #333;
}
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.list-group{
  margin: 1rem;
}
.loading-cursor {
  cursor: progress;
}
</style>
