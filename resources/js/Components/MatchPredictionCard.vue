<script setup lang="ts">
import axios from 'axios'
import { onMounted, ref, watch } from 'vue'

interface Prediction {
  home_win: number
  draw: number
  away_win: number
  home_goals: number
  away_goals: number
}

const props = defineProps({
  match: {
    type: Object,
    required: true
  },
  competition: {
    type: Object,
    required: true
  }
})

const loading = ref(false)
const error = ref<string | null>(null)
const prediction = ref<Prediction | null>(null)

function teamName(team: Record<string, string | undefined> | undefined, fallback: string) {
  return team?.shortname || team?.name || fallback
}

function hasPredictionPayload() {
  return Boolean(
    props.match
    && props.competition
    && (props.match.homeTeam || props.match.home_team)
    && (props.match.awayTeam || props.match.away_team)
    && props.competition.name
  )
}

async function fetchPrediction() {
  const response = await axios.post('/api/predictions/match', {
    match: props.match,
    competition: props.competition
  })

  return response.data
}

const loadPrediction = async () => {
  loading.value = true
  error.value = null
  prediction.value = null

  if (!hasPredictionPayload()) {
    error.value = 'No hay datos suficientes para generar la predicción.'
    loading.value = false
    return
  }

  try {
    prediction.value = await fetchPrediction()
  } catch (err) {
    console.error('Error fetching prediction:', err)
    error.value = 'No se pudo cargar la predicción. Por favor, inténtalo de nuevo más tarde.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadPrediction()
})

watch(() => props.match?.id, () => {
  loadPrediction()
})
</script>

<template>
  <div class="prediction-card">
    <h3 class="prediction-card__title">Predicción Futbolix AI</h3>

    <div v-if="loading" class="prediction-card__state">
      <p>Calculando predicción...</p>
    </div>

    <div v-else-if="error" class="prediction-card__state prediction-card__state--error">
      <p>{{ error }}</p>
    </div>

    <div v-else-if="prediction" class="prediction-card__content">
      <div class="prediction-card__grid">
        <div class="prediction-card__cell">
          <p class="prediction-card__team-name">
            {{ teamName(match.homeTeam || match.home_team, 'Local') }}
          </p>
          <p class="prediction-card__score">{{ prediction.home_goals }}</p>
          <p class="prediction-card__probability">{{ prediction.home_win }}%</p>
        </div>

        <div class="prediction-card__cell prediction-card__cell--center">
          <p class="prediction-card__separator">X</p>
          <p class="prediction-card__probability">{{ prediction.draw }}%</p>
        </div>

        <div class="prediction-card__cell">
          <p class="prediction-card__team-name">
            {{ teamName(match.awayTeam || match.away_team, 'Visitante') }}
          </p>
          <p class="prediction-card__score">{{ prediction.away_goals }}</p>
          <p class="prediction-card__probability">{{ prediction.away_win }}%</p>
        </div>
      </div>
    </div>

    <div v-else class="prediction-card__state">
      <p>La predicción no está disponible.</p>
    </div>
  </div>
</template>

<style scoped>
.prediction-card {
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(170, 120, 255, 0.2);
  border-radius: 20px;
  margin-top: 1rem;
  padding: 1rem 1.1rem;
  background:
    radial-gradient(circle at top left, rgba(205, 163, 255, 0.32), transparent 34%),
    radial-gradient(circle at bottom right, rgba(113, 76, 255, 0.28), transparent 32%),
    linear-gradient(135deg, #1b0f34 0%, #31145d 48%, #4b1f8a 100%);
  box-shadow: 0 16px 34px rgba(43, 16, 92, 0.28);
}

.prediction-card__title {
  margin: 0 0 1rem;
  font-size: 1rem;
  font-weight: 900;
  color: #f4ebff;
  letter-spacing: 0.01em;
}

.prediction-card__state {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 92px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.08);
  color: #efe3ff;
  text-align: center;
  backdrop-filter: blur(12px);
}

.prediction-card__state--error {
  background: rgba(255, 107, 152, 0.14);
  color: #ffdbe8;
}

.prediction-card__content {
  position: relative;
  z-index: 1;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.08);
  padding: 1rem 0.85rem;
  backdrop-filter: blur(14px);
}

.prediction-card__grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
  gap: 0.8rem;
  align-items: end;
}

.prediction-card__cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  min-width: 0;
}

.prediction-card__cell--center {
  justify-content: flex-end;
  gap: 0.8rem;
}

.prediction-card__team-name {
  min-height: 2.4em;
  margin: 0 0 0.45rem;
  font-size: 0.78rem;
  font-weight: 800;
  line-height: 1.2;
  color: #d7c0ff;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.prediction-card__score {
  margin: 0;
  font-size: clamp(2.2rem, 5vw, 3.1rem);
  font-weight: 900;
  line-height: 1;
  color: #ffffff;
  text-shadow: 0 0 18px rgba(194, 146, 255, 0.32);
}

.prediction-card__separator {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 900;
  line-height: 1;
  color: #d6abff;
}

.prediction-card__probability {
  margin: 0.4rem 0 0;
  font-size: 0.95rem;
  font-weight: 800;
  color: #f3e6ff;
}

@media (max-width: 640px) {
  .prediction-card__grid {
    gap: 0.55rem;
  }

  .prediction-card__team-name {
    font-size: 0.7rem;
  }

  .prediction-card__separator {
    font-size: 1.45rem;
  }
}
</style>
