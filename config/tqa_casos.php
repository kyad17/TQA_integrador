<?php

return [
    'Feature/AccesoTransporteTest' => [
        'test_invitado_necesita_iniciar_sesion' => 'Abre el panel administrativo de transporte como invitado y verifica que solicite iniciar sesión.',
        'test_afiliado_regresa_a_su_panel' => 'Intenta entrar al panel administrativo con una cuenta afiliada y comprueba la redirección al dashboard.',
        'test_login_conduce_a_la_portada' => 'Abre la ruta de inicio de sesión y comprueba que dirija a la portada del sindicato.',
        'test_login_exige_usuario_y_clave' => 'Envía el formulario de acceso vacío y verifica los errores del usuario y de la contraseña.',
    ],
    'Unit/FinanzasRiesgoDeudaTest' => [
        'clasifica_el_riesgo_por_las_bandas_de_monto_definidas' => 'Evalúa deudas de 0, 49,99, 50, 99,99 y 100 Bs y compara el nivel de riesgo con la banda esperada.',
        'los_agravantes_no_sacan_el_puntaje_de_la_banda_del_monto' => 'Aplica distintos agravantes y comprueba que el puntaje quede dentro de la banda del monto.',
        'genera_una_explicacion_y_accion_para_cada_deuda' => 'Consulta una deuda de 30 Bs y verifica su nivel, criterio, observación, acción y factores.',
    ],
    'Unit/GpsPositionsUpdatedTest' => [
        'normaliza_el_formato_del_simulador_y_emite_por_canal_privado' => 'Construye un evento GPS con una posición simulada y verifica sus coordenadas, fecha y canal privado.',
        'acepta_aliases_antiguos_de_coordenadas_y_velocidad' => 'Entrega una posición con los nombres de campos antiguos y verifica las coordenadas, la velocidad y el rumbo normalizados.',
    ],
    'Unit/MotorInteligenteServiceTest' => [
        'analizar_devuelve_estructura_correcta_sin_tablas' => 'Simula la ausencia de tablas y comprueba que el análisis devuelva cero vehículos procesados y un mensaje.',
        'resumen_dashboard_devuelve_estructura_sin_tablas' => 'Solicita el resumen sin tablas y revisa los indicadores de riesgo, deuda y GPS.',
        'tabla_analisis_devuelve_array_vacio_sin_tablas' => 'Consulta la tabla de análisis sin registros disponibles y comprueba que devuelva una lista vacía.',
        'info_modelo_devuelve_estructura' => 'Consulta la información del modelo y comprueba los campos de versión, método, arquitectura y configuración.',
        'estima_vueltas_cuando_el_gps_reporta_cero_o_un_valor_absurdo' => 'Introduce vueltas GPS de 0 y 999 y contrasta la estimación con distancias de 80 y 0,2 km.',
        'conserva_vueltas_reportadas_si_son_coherentes_con_la_distancia' => 'Entrega una secuencia de hasta diez vueltas y verifica que se conserven las diez para 160 km.',
        'filtra_coordenadas_timestamps_invalidos_y_duplicados' => 'Mezcla posiciones válidas, duplicadas y con fecha o coordenadas inválidas y verifica que solo quede una.',
    ],
    'Unit/NormalizadorCaracteristicasTest' => [
        'normaliza_entre_0_y_1' => 'Entrena con edades e ingresos y comprueba los valores normalizados en el mínimo, el punto medio y el máximo.',
        'lanza_excepcion_sin_entrenar' => 'Intenta normalizar una muestra antes del entrenamiento y comprueba que se produzca la excepción esperada.',
        'lanza_excepcion_con_dataset_vacio' => 'Intenta entrenar con un conjunto vacío y comprueba que se rechace.',
        'normalizar_vector_respeta_orden' => 'Normaliza dos características y comprueba el tamaño, el orden y los valores del vector.',
        'maneja_caracteristica_constante' => 'Entrena con un valor constante y verifica que se normalice a 0,5.',
        'persiste_y_recupera_estado' => 'Exporta el normalizador, lo reconstruye y comprueba su resultado con un valor intermedio.',
        'limita_valores_nuevos_al_rango_normalizado' => 'Prueba valores inferiores y superiores al intervalo entrenado y verifica que se limiten a 0 y 1.',
        'rechaza_caracteristicas_no_numericas' => 'Incluye texto en una característica numérica y comprueba que el entrenamiento lo rechace.',
        'usa_rangos_fijos_para_evitar_normalizadores_demasiado_estrechos' => 'Entrena con un rango fijo de distancia entre 0 y 220 y comprueba la escala obtenida para 165.',
        'detecta_caracteristicas_fuera_del_rango_entrenado' => 'Compara valores de vueltas dentro y fuera del intervalo y comprueba cuál se identifica como atípico.',
    ],
    'Unit/RendimientoLotesTest' => [
        'test_clasifica_diez_mil_deudas_en_menos_de_dos_segundos' => 'Clasifica 10.000 deudas sintéticas, comprueba que 5.000 sean de riesgo alto y mide el límite de dos segundos.',
        'test_normaliza_diez_mil_velocidades_en_menos_de_dos_segundos' => 'Normaliza 10.000 velocidades, comprueba su suma y verifica que el lote tarde menos de dos segundos.',
    ],
    'Unit/MlpClasificadorTest' => [
        'puede_entrenar_y_predecir' => 'Entrena con dos clases, revisa las métricas de entrenamiento y verifica que la predicción sea una clase válida.',
        'no_se_puede_predecir_sin_entrenar' => 'Solicita una predicción antes de entrenar y comprueba la excepción esperada.',
        'probabilidades_suman_uno' => 'Calcula las probabilidades tras entrenar y comprueba que sumen uno con una tolerancia de 0,01.',
        'precision_en_rango_valido' => 'Evalúa la precisión con cuatro muestras y verifica que esté entre 0 y 1 y que la predicción sea válida.',
        'puede_guardar_y_cargar_modelo' => 'Guarda el modelo entrenado en un archivo temporal, lo carga y compara la predicción de ambos modelos.',
        'carga_devuelve_null_si_no_existe' => 'Intenta cargar un archivo de modelo inexistente y verifica que la respuesta sea nula.',
        'entrena_con_datos_reales_3_clases' => 'Entrena con tres niveles de riesgo y comprueba la pérdida final y la precisión en las muestras de entrenamiento.',
        'lanza_excepcion_con_dataset_vacio' => 'Entrega muestras y etiquetas vacías y verifica que el clasificador rechace el entrenamiento.',
        'rechaza_dimensiones_inconsistentes' => 'Entrena con vectores de distintas dimensiones y comprueba que se rechacen.',
        'rechaza_etiquetas_desconocidas' => 'Entrega una etiqueta fuera de las clases configuradas y verifica la excepción esperada.',
    ],
    'Unit/EntrenadorModeloPerfilTest' => [
        'acepta_los_perfiles_integral_y_gps' => 'Crea un entrenador para el perfil integral y otro para GPS y verifica que ambos sean válidos.',
        'rechaza_un_perfil_desconocido' => 'Intenta crear un entrenador con un perfil desconocido y verifica que se rechace.',
    ],
];
