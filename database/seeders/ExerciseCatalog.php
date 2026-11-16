<?php

namespace Database\Seeders;

class ExerciseCatalog
{
    /**
     * Curated exercise catalog grouped by muscle group, with at least five
     * options per group and broad equipment coverage.
     *
     * @return array<int, array{name: string, muscle_group: string, equipment: string}>
     */
    public static function all(): array
    {
        return [
            // Peito
            ['name' => 'Supino reto com barra', 'muscle_group' => 'chest', 'equipment' => 'barbell'],
            ['name' => 'Supino inclinado com halteres', 'muscle_group' => 'chest', 'equipment' => 'dumbbell'],
            ['name' => 'Crucifixo com halteres', 'muscle_group' => 'chest', 'equipment' => 'dumbbell'],
            ['name' => 'Flexão de braço', 'muscle_group' => 'chest', 'equipment' => 'none'],
            ['name' => 'Crossover na polia', 'muscle_group' => 'chest', 'equipment' => 'cable'],
            ['name' => 'Supino máquina', 'muscle_group' => 'chest', 'equipment' => 'machine'],

            // Costas
            ['name' => 'Puxada frontal na polia', 'muscle_group' => 'back', 'equipment' => 'cable'],
            ['name' => 'Remada curvada com barra', 'muscle_group' => 'back', 'equipment' => 'barbell'],
            ['name' => 'Remada unilateral com halter', 'muscle_group' => 'back', 'equipment' => 'dumbbell'],
            ['name' => 'Barra fixa', 'muscle_group' => 'back', 'equipment' => 'none'],
            ['name' => 'Remada baixa na máquina', 'muscle_group' => 'back', 'equipment' => 'machine'],
            ['name' => 'Pullover com halter', 'muscle_group' => 'back', 'equipment' => 'dumbbell'],
            ['name' => 'Remada com faixa elástica', 'muscle_group' => 'back', 'equipment' => 'resistance_band'],

            // Dorsais
            ['name' => 'Puxada supinada', 'muscle_group' => 'lats', 'equipment' => 'cable'],
            ['name' => 'Puxada aberta na máquina', 'muscle_group' => 'lats', 'equipment' => 'machine'],
            ['name' => 'Barra fixa pronada', 'muscle_group' => 'lats', 'equipment' => 'none'],
            ['name' => 'Remada serrote', 'muscle_group' => 'lats', 'equipment' => 'dumbbell'],
            ['name' => 'Puxada com faixa', 'muscle_group' => 'lats', 'equipment' => 'resistance_band'],

            // Trapézio
            ['name' => 'Encolhimento com barra', 'muscle_group' => 'traps', 'equipment' => 'barbell'],
            ['name' => 'Encolhimento com halteres', 'muscle_group' => 'traps', 'equipment' => 'dumbbell'],
            ['name' => 'Remada alta na polia', 'muscle_group' => 'traps', 'equipment' => 'cable'],
            ['name' => 'Encolhimento na máquina', 'muscle_group' => 'traps', 'equipment' => 'machine'],
            ['name' => 'Face pull', 'muscle_group' => 'traps', 'equipment' => 'cable'],

            // Ombros
            ['name' => 'Desenvolvimento militar', 'muscle_group' => 'shoulders', 'equipment' => 'barbell'],
            ['name' => 'Elevação lateral', 'muscle_group' => 'shoulders', 'equipment' => 'dumbbell'],
            ['name' => 'Elevação frontal', 'muscle_group' => 'shoulders', 'equipment' => 'dumbbell'],
            ['name' => 'Desenvolvimento na máquina', 'muscle_group' => 'shoulders', 'equipment' => 'machine'],
            ['name' => 'Elevação lateral na polia', 'muscle_group' => 'shoulders', 'equipment' => 'cable'],
            ['name' => 'Pike push-up', 'muscle_group' => 'shoulders', 'equipment' => 'none'],

            // Bíceps
            ['name' => 'Rosca direta com barra', 'muscle_group' => 'biceps', 'equipment' => 'barbell'],
            ['name' => 'Rosca alternada', 'muscle_group' => 'biceps', 'equipment' => 'dumbbell'],
            ['name' => 'Rosca martelo', 'muscle_group' => 'biceps', 'equipment' => 'dumbbell'],
            ['name' => 'Rosca Scott na máquina', 'muscle_group' => 'biceps', 'equipment' => 'machine'],
            ['name' => 'Rosca na polia', 'muscle_group' => 'biceps', 'equipment' => 'cable'],
            ['name' => 'Rosca com faixa elástica', 'muscle_group' => 'biceps', 'equipment' => 'resistance_band'],

            // Tríceps
            ['name' => 'Tríceps na corda', 'muscle_group' => 'triceps', 'equipment' => 'cable'],
            ['name' => 'Tríceps testa', 'muscle_group' => 'triceps', 'equipment' => 'barbell'],
            ['name' => 'Tríceps francês', 'muscle_group' => 'triceps', 'equipment' => 'dumbbell'],
            ['name' => 'Mergulho no banco', 'muscle_group' => 'triceps', 'equipment' => 'none'],
            ['name' => 'Tríceps na máquina', 'muscle_group' => 'triceps', 'equipment' => 'machine'],
            ['name' => 'Extensão de tríceps com faixa', 'muscle_group' => 'triceps', 'equipment' => 'resistance_band'],

            // Antebraços
            ['name' => 'Rosca de punho com barra', 'muscle_group' => 'forearms', 'equipment' => 'barbell'],
            ['name' => 'Rosca inversa', 'muscle_group' => 'forearms', 'equipment' => 'barbell'],
            ['name' => 'Farmer walk', 'muscle_group' => 'forearms', 'equipment' => 'dumbbell'],
            ['name' => 'Rosca de punho com halter', 'muscle_group' => 'forearms', 'equipment' => 'dumbbell'],
            ['name' => 'Hand grip', 'muscle_group' => 'forearms', 'equipment' => 'other'],

            // Core / abdômen
            ['name' => 'Prancha abdominal', 'muscle_group' => 'core', 'equipment' => 'none'],
            ['name' => 'Abdominal remador', 'muscle_group' => 'core', 'equipment' => 'none'],
            ['name' => 'Crunch', 'muscle_group' => 'core', 'equipment' => 'none'],
            ['name' => 'Elevação de pernas', 'muscle_group' => 'core', 'equipment' => 'none'],
            ['name' => 'Abdominal na máquina', 'muscle_group' => 'core', 'equipment' => 'machine'],
            ['name' => 'Abdominal com bola medicinal', 'muscle_group' => 'core', 'equipment' => 'medicine_ball'],
            ['name' => 'Woodchopper na polia', 'muscle_group' => 'core', 'equipment' => 'cable'],

            // Oblíquos
            ['name' => 'Prancha lateral', 'muscle_group' => 'obliques', 'equipment' => 'none'],
            ['name' => 'Russian twist', 'muscle_group' => 'obliques', 'equipment' => 'medicine_ball'],
            ['name' => 'Rotação de tronco na polia', 'muscle_group' => 'obliques', 'equipment' => 'cable'],
            ['name' => 'Elevação de pernas oblíqua', 'muscle_group' => 'obliques', 'equipment' => 'none'],
            ['name' => 'Rotação com faixa elástica', 'muscle_group' => 'obliques', 'equipment' => 'resistance_band'],

            // Lombar
            ['name' => 'Extensão lombar na máquina', 'muscle_group' => 'lower_back', 'equipment' => 'machine'],
            ['name' => 'Superman', 'muscle_group' => 'lower_back', 'equipment' => 'none'],
            ['name' => 'Bom dia com barra', 'muscle_group' => 'lower_back', 'equipment' => 'barbell'],
            ['name' => 'Extensão lombar com anilha', 'muscle_group' => 'lower_back', 'equipment' => 'plate'],
            ['name' => 'Hiperextensão no banco', 'muscle_group' => 'lower_back', 'equipment' => 'bench'],

            // Glúteos
            ['name' => 'Hip thrust com barra', 'muscle_group' => 'glutes', 'equipment' => 'barbell'],
            ['name' => 'Glúteo na máquina', 'muscle_group' => 'glutes', 'equipment' => 'machine'],
            ['name' => 'Coice na polia', 'muscle_group' => 'glutes', 'equipment' => 'cable'],
            ['name' => 'Ponte glútea', 'muscle_group' => 'glutes', 'equipment' => 'none'],
            ['name' => 'Abdução na máquina', 'muscle_group' => 'glutes', 'equipment' => 'machine'],
            ['name' => 'Agachamento sumô com kettlebell', 'muscle_group' => 'glutes', 'equipment' => 'kettlebell'],
            ['name' => 'Hip thrust no banco', 'muscle_group' => 'glutes', 'equipment' => 'bench'],

            // Quadríceps
            ['name' => 'Agachamento livre', 'muscle_group' => 'quadriceps', 'equipment' => 'barbell'],
            ['name' => 'Leg press 45°', 'muscle_group' => 'quadriceps', 'equipment' => 'machine'],
            ['name' => 'Cadeira extensora', 'muscle_group' => 'quadriceps', 'equipment' => 'machine'],
            ['name' => 'Agachamento búlgaro', 'muscle_group' => 'quadriceps', 'equipment' => 'dumbbell'],
            ['name' => 'Afundo com halteres', 'muscle_group' => 'quadriceps', 'equipment' => 'dumbbell'],
            ['name' => 'Agachamento goblet', 'muscle_group' => 'quadriceps', 'equipment' => 'kettlebell'],
            ['name' => 'Step-up no banco', 'muscle_group' => 'quadriceps', 'equipment' => 'step'],
            ['name' => 'Agachamento com salto', 'muscle_group' => 'quadriceps', 'equipment' => 'none'],

            // Posteriores de coxa
            ['name' => 'Stiff com barra', 'muscle_group' => 'hamstrings', 'equipment' => 'barbell'],
            ['name' => 'Mesa flexora', 'muscle_group' => 'hamstrings', 'equipment' => 'machine'],
            ['name' => 'Cadeira flexora', 'muscle_group' => 'hamstrings', 'equipment' => 'machine'],
            ['name' => 'Levantamento terra romeno', 'muscle_group' => 'hamstrings', 'equipment' => 'dumbbell'],
            ['name' => 'Ponte unilateral', 'muscle_group' => 'hamstrings', 'equipment' => 'none'],
            ['name' => 'Bom dia com faixa', 'muscle_group' => 'hamstrings', 'equipment' => 'resistance_band'],

            // Panturrilhas
            ['name' => 'Panturrilha em pé na máquina', 'muscle_group' => 'calves', 'equipment' => 'machine'],
            ['name' => 'Panturrilha sentado', 'muscle_group' => 'calves', 'equipment' => 'machine'],
            ['name' => 'Panturrilha no leg press', 'muscle_group' => 'calves', 'equipment' => 'machine'],
            ['name' => 'Panturrilha unilateral com halter', 'muscle_group' => 'calves', 'equipment' => 'dumbbell'],
            ['name' => 'Panturrilha no step', 'muscle_group' => 'calves', 'equipment' => 'step'],

            // Adutores
            ['name' => 'Cadeira adutora', 'muscle_group' => 'adductors', 'equipment' => 'machine'],
            ['name' => 'Copenhagen plank', 'muscle_group' => 'adductors', 'equipment' => 'none'],
            ['name' => 'Agachamento sumô com halter', 'muscle_group' => 'adductors', 'equipment' => 'dumbbell'],
            ['name' => 'Adução com faixa', 'muscle_group' => 'adductors', 'equipment' => 'resistance_band'],
            ['name' => 'Adução na polia', 'muscle_group' => 'adductors', 'equipment' => 'cable'],

            // Abdutores
            ['name' => 'Cadeira abdutora', 'muscle_group' => 'abductors', 'equipment' => 'machine'],
            ['name' => 'Caminhada lateral com faixa', 'muscle_group' => 'abductors', 'equipment' => 'resistance_band'],
            ['name' => 'Abdução na polia', 'muscle_group' => 'abductors', 'equipment' => 'cable'],
            ['name' => 'Concha com faixa', 'muscle_group' => 'abductors', 'equipment' => 'resistance_band'],
            ['name' => 'Abdução em pé com kettlebell', 'muscle_group' => 'abductors', 'equipment' => 'kettlebell'],

            // Flexores do quadril
            ['name' => 'Elevação de joelho na barra', 'muscle_group' => 'hip_flexors', 'equipment' => 'none'],
            ['name' => 'Elevação de joelho na polia', 'muscle_group' => 'hip_flexors', 'equipment' => 'cable'],
            ['name' => 'Mountain climber', 'muscle_group' => 'hip_flexors', 'equipment' => 'none'],
            ['name' => 'Caminhada com faixa nas pernas', 'muscle_group' => 'hip_flexors', 'equipment' => 'resistance_band'],
            ['name' => 'Elevação de pernas suspensa', 'muscle_group' => 'hip_flexors', 'equipment' => 'suspension_strap'],

            // Pescoço
            ['name' => 'Flexão de pescoço', 'muscle_group' => 'neck', 'equipment' => 'none'],
            ['name' => 'Extensão de pescoço', 'muscle_group' => 'neck', 'equipment' => 'none'],
            ['name' => 'Flexão lateral de pescoço', 'muscle_group' => 'neck', 'equipment' => 'none'],
            ['name' => 'Encolhimento para pescoço com halter', 'muscle_group' => 'neck', 'equipment' => 'dumbbell'],
            ['name' => 'Isometria de pescoço', 'muscle_group' => 'neck', 'equipment' => 'none'],

            // Corpo inteiro
            ['name' => 'Burpee', 'muscle_group' => 'full_body', 'equipment' => 'none'],
            ['name' => 'Thruster com halteres', 'muscle_group' => 'full_body', 'equipment' => 'dumbbell'],
            ['name' => 'Swing com kettlebell', 'muscle_group' => 'full_body', 'equipment' => 'kettlebell'],
            ['name' => 'Clean and press', 'muscle_group' => 'full_body', 'equipment' => 'barbell'],
            ['name' => 'Remada alta com barra', 'muscle_group' => 'full_body', 'equipment' => 'barbell'],
            ['name' => 'Battle rope', 'muscle_group' => 'full_body', 'equipment' => 'other'],

            // Cardio
            ['name' => 'Esteira', 'muscle_group' => 'cardio', 'equipment' => 'cardio'],
            ['name' => 'Bicicleta ergométrica', 'muscle_group' => 'cardio', 'equipment' => 'cardio'],
            ['name' => 'Elíptico', 'muscle_group' => 'cardio', 'equipment' => 'cardio'],
            ['name' => 'Remo ergômetro', 'muscle_group' => 'cardio', 'equipment' => 'cardio'],
            ['name' => 'Corda de pular', 'muscle_group' => 'cardio', 'equipment' => 'cardio'],
            ['name' => 'Escada / simulador de escada', 'muscle_group' => 'cardio', 'equipment' => 'cardio'],
            ['name' => 'Caminhada incline', 'muscle_group' => 'cardio', 'equipment' => 'cardio'],
            ['name' => 'Circuito cardio com step', 'muscle_group' => 'cardio', 'equipment' => 'step'],

            // Mobilidade
            ['name' => 'Cat-cow', 'muscle_group' => 'mobility', 'equipment' => 'none'],
            ['name' => 'Mobilidade de quadril com faixa', 'muscle_group' => 'mobility', 'equipment' => 'resistance_band'],
            ['name' => 'Alongamento de peitoral na parede', 'muscle_group' => 'mobility', 'equipment' => 'none'],
            ['name' => 'Rotação torácica', 'muscle_group' => 'mobility', 'equipment' => 'none'],
            ['name' => 'Mobilidade de ombro com bastão', 'muscle_group' => 'mobility', 'equipment' => 'other'],

            // Pernas (compatibilidade com o catálogo anterior)
            ['name' => 'Cadeira extensora unilateral', 'muscle_group' => 'legs', 'equipment' => 'machine'],
            ['name' => 'Leg press unilateral', 'muscle_group' => 'legs', 'equipment' => 'machine'],
            ['name' => 'Afundo caminhando', 'muscle_group' => 'legs', 'equipment' => 'dumbbell'],
            ['name' => 'Agachamento com faixa', 'muscle_group' => 'legs', 'equipment' => 'resistance_band'],
            ['name' => 'Stiff com kettlebell', 'muscle_group' => 'legs', 'equipment' => 'kettlebell'],
        ];
    }
}
