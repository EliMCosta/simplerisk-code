<?php
/***********************************************************************
 * This Source Code Form is subject to the terms of the Mozilla Public *
 * License, v. 2.0. If a copy of the MPL was not distributed with this *
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.            *
 ***********************************************************************/

ini_set('default_charset', 'utf-8');
define('DATETIME', 'Y-m-d g:i A T');
define('DATETIMESIMPLE', 'Y-m-d H:i');
define('DATESIMPLE', 'Y-m-d');

$lang = array(
    'Home'=>'Home',
    'RiskManagement'=>'Gestion de Riesgo',
    'Reporting'=>'Reportes',
    'Configure'=>'Configuración',
    'MyProfile'=>'Mi Perfil',
    'Logout'=>'Salir',
    'LogInHere'=>'Logearse Aqui',
    'Username'=>'Nombre de Usuario',
    'Password'=>'Contraseña',
    'ForgotYourPassword'=>'Olvido su Contraseña',
    'Login'=>'Ingresar',
    'Reset'=>'Reiniciar',
    'Send'=>'Enviar',
    'Update'=>'Actualizar',
    'SendPasswordResetEmail'=>'Envio Reinicio de Contraseña por Correo',
    'PasswordReset'=>'Reiniciar Contraseña',
    'ResetToken'=>'Reinicio de Token',
    'RepeatPassword'=>'Repetir Contraseña',
    'Submit'=>'Acceder',
    'ProfileDetails'=>'Información de Perfil',
    'LastLogin'=>'Ultimo Ingreso',
    'ChangePassword'=>'Cambio de contraseña',
    'CurrentPassword'=>'Contraseña actual',
    'NewPassword'=>'Nueva Contraseña',
    'ConfirmPassword'=>'Repetir Contraseña',
    'ConfigureRiskFormula'=>'Configuracion Formula de Riesgo',
    'ConfigureReviewSettings'=>'Chequeo de Configuración',
    'AddAndRemoveValues'=>'Agregar & Quitar Valores',
    'UserManagement'=>'Gestion de Usuario',
    'RedefineNamingConventions'=>'Redefine Naming Conventions',
    'AuditTrail'=>'Auditoria',
    'Extras'=>'Extras',
    'Announcements'=>'Noticias',
    'About'=>'Nosotros',
    'Impact'=>'Impacto',
    'Likelihood'=>'Probabilidad',
    'MitigationEffort'=>'Esfuerzos de Mitigación',
    'Change'=>'Cambio',
    'to'=>'para',
    'AddANewUser'=>'Agregar Nuevo Usuario',
    'Type'=>'Tipo',
    'FullName'=>'Nombre Completo',
    'EmailAddress'=>'E-mail',
    'Teams'=>'Equipo(s)',
    'ALL'=>'Todo',
    'NONE'=>'Nada',
    'UserResponsibilities'=>'Responsabilidades de Usuario',
    'AbleToSubmitNewRisks'=>'Poder presentar nuevos riesgos',
    'AbleToModifyExistingRisks'=>'Poder modificar un riesgo existente',
    'AbleToCloseRisks'=>'poder cerrar un riesgo',
    'AbleToPlanMitigations'=>'poder planificar las mitigaciones',
    'AbleToReviewLowRisks'=>'poder revisar los riesgos bajos',
    'AbleToReviewMediumRisks'=>'poder revisar los riesgos medios',
    'AbleToReviewHighRisks'=>'poder revisar los riesgos altos',
    'AllowAccessToConfigureMenu'=>'Permitir accesos a "Configuration" Menu',
    'MultiFactorAuthentication'=>'Multi-Factor de Autenticación',
    'None'=>'Nada',
    'Add'=>'Agregar',
    'ViewDetailsForUser'=>'Ver detalles por Usuario',
    'DetailsForUser'=>'Detalle por Usuario',
    'Select'=>'Seleccionar',
    'EnableAndDisableUsers'=>'Activar y Desactivar Usuarios',
    'EnableAndDisableUsersHelp'=>'usa esta caracteristicas para activar o desactivar Autenticacion de usuarios mientras se mantiene la pista de auditorÌa en las actividades del usuario',
    'DisableUser'=>'Desactivar Usuario',
    'Disable'=>'Desactivar',
    'EnableUser'=>'Habilitar Usuario',
    'Enable'=>'Activar',
    'DeleteAnExistingUser'=>'Eliminar Usuario Existente',
    'DeleteCurrentUser'=>'Eliminar Usuario Actual',
    'Delete'=>'Borrar',
    'SendPasswordResetEmailForUser'=>'Enviar reinicio de contraseña por correo',
    'Category'=>'Categoria',
    'AddNewCategoryNamed'=>'Agregar Nueva Categoria',
    'DeleteCurrentCategoryNamed'=>'Borrar Nombre de la Categoria Actual',
    'Team'=>'Equipo',
    'AddNewTeamNamed'=>'Agregar Nuevo Equipo',
    'DeleteCurrentTeamNamed'=>'Eliminar Nombre del Equipo',
    'Technology'=>'Tecnologia',
    'AddNewTechnologyNamed'=>'Agregar Nueva Tecnologia',
    'DeleteCurrentTechnologyNamed'=>'Borrar Nombre de la Tecnologia',
    'SiteLocation'=>'Lugar/Localidad',
    'AddNewSiteLocationNamed'=>'Agregar Nuevo nombre Sitio/Localidad',
    'DeleteCurrentSiteLocationNamed'=>'Borrar Nombre Sitio/Localidad',
    'ControlRegulation'=>'Regulation de Controles',
    'AddNewControlRegulationNamed'=>'Agregar Nueva Regulacion de control',
    'DeleteCurrentControlRegulationNamed'=>'Borrar Regulacion de control',
    'RiskPlanningStrategy'=>'Estrategia de planificación de Riesgo',
    'AddNewRiskPlanningStrategyNamed'=>'Agregar Nueva Estrategia de Planificación de Riesgo',
    'DeleteCurrentRiskPlanningStrategyNamed'=>'Borrar Estrategia de Planificación de Riesgo',
    'CloseReason'=>'Cerrar Razon',
    'AddNewCloseReasonNamed'=>'Agregar Nuevo Cierre de razon',
    'DeleteCurrentCloseReasonNamed'=>'Borrar Cierre de razon',
    'IWantToReviewHighRiskEvery'=>'Revisar cada Riesgo Alto',
    'IWantToReviewMediumRiskEvery'=>'Revisar cada Riesgo Medio',
    'IWantToReviewLowRiskEvery'=>'Revisar cada Riesgo Bajo',
    'days'=>'Dias',
    'MyClassicRiskFormulaIs'=>'My Formula de Riesgo Clasico es',
    'RISK'=>'Riesgo',
    'IConsiderHighRiskToBeAnythingGreaterThan'=>'Considerar Riesgos Altos que sean mayor que',
    'IConsiderMediumRiskToBeLessThanAboveButGreaterThan'=>'Considerar Riesgos Medios menor que los anteriores, pero mayor que',
    'IConsiderlowRiskToBeLessThanAboveButGreaterThan'=>'Considerar Riesgos Bajos menor que los anteriores, pero mayor que',
    'HighRisk'=>'Riesgo Alto',
    'MediumRisk'=>'Riesgo Medio',
    'LowRisk'=>'Riesgo Bajo',
    'Irrelevant'=>'Irrelevante',
    'SubmitYourRisks'=>'Aceptar los Riesgos',
    'PlanYourMitigations'=>'Planear tus Mitigaciones',
    'PerformManagementReviews'=>'Revision de la Gestion Realizada',
    'PrioritizeForProjectPlanning'=>'Priorizar por Planificacion de Proyectos',
    'ReviewRisksRegularly'=>'Revision de Riesgos Regularmente',
    'DocumentANewRisk'=>'Documenta un Nuevo Riesgo',
    'UseThisFormHelp'=>'Utilice este formulario para documentar un nuevo riesgo para su consideración en el proceso de gestión de riesgos',
    'Subject'=>'Asunto',
    'ExternalReferenceId'=>'ID Referencia Externa',
    'ControlNumber'=>'Numero de Control',
    'Owner'=>'Propietario',
    'OwnersManager'=>'Directorio Propietario',
    'RiskScoringMethod'=>'Metodo de Escore de riesgo',
    'CurrentLikelihood'=>'Probabilidad Actual',
    'CurrentImpact'=>'Impacto Actual',
    'RiskAssessment'=>'Valoración de Riesgo',
    'AdditionalNotes'=>'Notas Adicionales',
    'UNREVIEWED'=>'Sin Revisar',
    'PASTDUE'=>'Vencida',
    'ID'=>'ID',
    'Status'=>'Estatus',
    'Risk'=>'Riesgo',
    'DaysOpen'=>'Dias Apertura',
    'CalculatedRisk'=>'Calcular Riesgo',
    'SubmittedBy'=>'Enviado por',
    'NextReviewDate'=>'Nueva Fecha de Revision',
    'CVSSRiskScoring'=>'CVSS Calificación de Riesgo',
    'DREADRiskScoring'=>'DREAD Calificación de riesgo',
    'OWASPRiskScoring'=>'OWASP Calificación de riesgo',
    'CustomRiskScoring'=>'Calificación de Riesgo a Medida',
    'MitigationPlanningHelp'=>'A continuación se muestra la lista de los riesgos presentados que requieren la planificación de mitigación',
    'ManagementReviewHelp'=>'A continuación se muestra la lista de los riesgos presentados que requieren un examen de gestión',
    'Submitted'=>'Enviado',
    'MitigationPlanned'=>'Planificacion de Mitigación',
    'ManagementReview'=>'Revision de Gestión',
    'No'=>'No',
    'Yes'=>'Si',
    'AddAndRemoveProjects'=>'Agregar y Eliminar Proyectos',
    'AddAndRemoveProjectsHelp'=>'Agregar y eliminar proyectos en orden asociado alos riesgos multiples juntos por priorización',
    'AddNewProjectNamed'=>'Asignar Nuevo Nombre de Proyecto',
    'DeleteCurrentProjectNamed'=>'Eliminar Nombre de Proyecto',
    'AddUnassignedRisksToProjects'=>'Agregar Riesgos no Asignados a Proyectos',
    'AddUnassignedRisksToProjectsHelp'=>'Extraer y Soltar los riesgos no asignados marcados para su consideración como un proyecto en la pestaÒa proyecto adecuado',
    'PrioritizeProjects'=>'Dar Prioridad a los Proyectos',
    'PrioritizeProjectsHelp'=>'Mueva proyectos alrededor y cambiar el orden de prioridades. Una vez terminado, no olvides de pulsar el botón "Actualizar" botón para guardar los cambios',
    'SaveRisksToProjects'=>'Guardar Riesgos a Proyectos',
    'RiskId'=>'Riesgos ID',
    'RiskActions'=>'Acciones de Riesgo',
    'ReopenRisk'=>'Reabrir un Riesgo',
    'CloseRisk'=>'Cerrar Riesgo',
    'EditRisk'=>'Editar Riesgo',
    'PlanAMitigation'=>'Planear una Mitigación',
    'PerformAReview'=>'Realizar Opinion',
    'AddAComment'=>'Agregar un Comentario',
    'ShowRiskScoringDetails'=>'Mostrar Riesgo Detallados por Valoración',
    'HideRiskScoringDetails'=>'Ocultar Riesgo Detallados por Valoración',
    'Details'=>'Detalles',
    'SubmissionDate'=>'Fecha de Envio',
    'DateSubmitted'=>'Fecha de Entrega',
    'EditDetails'=>'Editar Detalles',
    'Mitigation'=>'Mitigación',
    'MitigationDate'=>'Fecha de Mitigación',
    'PlanningStrategy'=>'Planificacion de Estrategia',
    'CurrentSolution'=>'Solucion Actual',
    'SecurityRequirements'=>'Requerimientos de Seguridad',
    'SecurityRecommendations'=>'Recomendaciones de Seguridad',
    'EditMitigation'=>'Editar una Mitigación',
    'LastReview'=>'Ultima Revisión',
    'ReviewDate'=>'Fecha de Revisión',
    'Reviewer'=>'Revisiones',
    'Review'=>'Revisión',
    'NextStep'=>'Siguiente Paso',
    'Comments'=>'Comentarios',
    'ViewAllReviews'=>'Ver todas las Revisiones',
    'ReviewHistory'=>'Historial de Revisiones',
    'Comment'=>'Comentario',
    'ClassicRiskScoring'=>'Calificación de Riesgos Clasicos',
    'RiskScoringActions'=>'Calificación de Acciones de Riesgo',
    'UpdateClassicScore'=>'Actualiza Calificación Clasica',
    'ScoreBy'=>'Calificación por',
    'RISKClassicExp1'=>'Riesgo = ( Probabilidad x Impacto + 2(Impacto) )',
    'RISKClassicExp2'=>'Riesgo = ( Probabilidad x Impacto + Impacto )',
    'RISKClassicExp3'=>'Riesgo = ( Probabilidad x Impacto )',
    'RISKClassicExp4'=>'Riesgo = ( Probabilidad x Impacto + Probabilidad )',
    'RISKClassicExp5'=>'Riesgo = ( Probabilidad x Impacto + 2(Probabilidad) )',
    'Reason'=>'Razon',
    'CloseOutInformation'=>'Cierre-Afuera Información',
    'SubmitManagementReview'=>'Enviar Opinion de Gestión',
    'SubmitRiskMitigation'=>'Enviar Riesgo de Mitigación',
    'RiskDashboard'=>'Tablero de Riesgo',
    'RiskTrend'=>'Tendencia de Riesgo',
    'AllOpenRisksAssignedToMeByRiskLevel'=>'Todos los Riesgos Abiertos Asignados a Mi por Nivel de Riesgo',
    'AllOpenRisksByRiskLevel'=>'Todos los Riesgos Abiertos por Nivel de Riesgo',
    'AllOpenRisksConsideredForProjectsByRiskLevel'=>'Todos los Riesgos Considerados por Proyectos por Nivel de Riesgo',
    'AllOpenRisksAcceptedUntilNextReviewByRiskLevel'=>'Todos los Riesgos Abiertos Aceptados Antes de la Siguiente Revisión por Nivel de Riesgo',
    'AllOpenRisksToSubmitAsAProductionIssueByRiskLevel'=>'Todos los Riesgos Abiertos por Enviar con Problemas de Producción por Nivel de Riesgo',
    'AllOpenRisksByScoringMethod'=>'Todos los Riesgos Abiertos por Metodo de Calificación',
    'AllClosedRisksByRiskLevel'=>'Todos los Riesgos Abiertos por Nivel de Riesgo',
    'SubmittedRisksByDate'=>'Riesgos Enviados por Fecha',
    'MitigationsByDate'=>'Mitigaciones por Fecha',
    'ManagementReviewsByDate'=>'Opinion de Gestion por Fecha',
    'ProjectsAndRisksAssigned'=>'Proyectos y Riesgos Asignados',
    'OpenRisks'=>'Riesgos Abiertos',
    'ClosedRisks'=>'Riesgos Cerrados',
    'ReportMyOpenHelp'=>'Este informe muestra todos los riesgos abiertos que tienen al usuario actual como el dueño o gerente asociado con el riesgo ordenado por nivel de riesgo',
    'ReportOpenHelp'=>'Este informe muestra todos los riesgos abiertos ordenados por nivel de riesgo',
    'ReportProjectsHelp'=>'Este informe muestra todos los riesgos abiertos considerados para proyectos ordenados por nivel de riesgo',
    'ReportNextReviewHelp'=>'Este informe muestra todos los riesgos abiertos aceptados hasta la próxima revisión ordenados por nivel de riesgo',
    'ReportProductionIssuesHelp'=>'Este informe muestra todos los riesgos abiertos presentados como problemas de producción ordenados por nivel de riesgo',
    'ReportRiskScoringHelp'=>'Este informe muestra todos los mÈtodos de calificación de riesgos y las calificaciones de riesgos utilizados por cada uno',
    'ReportClosedHelp'=>'Este informe muestra todos los riesgos cerrados ordenados por nivel de riesgo',
    'ReportSubmittedByDateHelp'=>'Este informe muestra todos los riesgos ordenados por fecha de presentación',
    'ReportMitigationsByDateHelp'=>'Este informe muestra todas las mitigaciones previstas ordenados por fecha de mitigación',
    'ReportMgmtReviewsByDateHelp'=>'Este informe muestra todas las revisiones por la dirección ordenada por fecha de revisión',
    'ReportProjectsAndRisksHelp'=>'Este informe muestra todos los proyectos y los riesgos que se les asignan',
    'Language'=>'Idioma',
    'AllOpenRisksNeedingReview'=>'Todo riesgo abiertas en las cuales necesitan una opinión',
    'ReportReviewNeededHelp'=>'Este informe muestra todos los riesgos abiertos que necesitan un examen de la gestión',
    'CustomValue'=>'Valor Personalizado',
    'ClosedRisksByDate'=>'Riesgos Cerrado por Fecha',
    'DateClosed'=>'Fecha Cerrada',
    'ClosedBy'=>'Cerrado Por',
    'ReportClosedByDateHelp'=>'Este informe muestra todos los riesgos ordenados por fecha de cierre',
    'AllOpenRisksByTeam'=>'Todos Riesgo Abiertas por Equipo',
    'ReportRiskTeamsHelp'=>'Este informe muestra todos los equipos y los riesgos asignados a cada',
    'Unassigned'=>'Sin Asignar',
    'AllOpenRisksByTechnology'=>'Todo Riesgo Abiertas por la Tecnología',
    'ReportRiskTechnologiesHelp'=>'Este informe muestra todas las tecnologías y los riesgos asignados a cada',
    'RiskLevel'=>'Nivel de Riesgo',
    'BasedOnTheCurrentRiskScore'=>'Sobre la base de la puntuación de riesgo actual, la próxima fecha de revisión será ',
    'WouldYouLikeToUseADifferentDate'=>'Le gustaría utilizar una fecha diferente en su lugar?',
    'RisksOpenedAndClosedOverTime'=>'Riesgos Abre y se Cierra en el Tiempo',
    'AllRiskScoresAreAdjusted'=>'Todas las puntuaciones de riesgo se ajustan para caber en una escala de 0-10.',
    'DetermineProjectStatus'=>'Determinar Project Status',
    'ProjectStatusHelp'=>'Coloca los proyectos en baldes sobre la base de su estado actual.',
    'ActiveProjects'=>'Proyectos Activos',
    'OnHoldProjects'=>'Proyectos en Pausa',
    'CompletedProjects'=>'Proyectos Finalizados',
    'CancelledProjects'=>'Proyectos Cancelados',
    'UpdateProjectStatuses'=>'Actualizar Status de Proyecto',
    'HighRiskReport'=>'Informe de Alto Riesgo',
    'TotalOpenRisks'=>'Total de Riesgos Abiertas',
    'TotalHighRisks'=>'Total de Altos Riesgos',
    'HighRiskPercentage'=>'Porcentaje de Alto Riesgo',
    'UpdateClassicScore'=>'Actualización Classic Puntuación',
    'CurrentLikelihood'=>'Probabilidad Actual',
    'CurrentImpact'=>'Impacto Actual',   
    'UpdateCVSSScore'=>'Actualización CVSS Puntuación',
    'BaseScoreMetrics'=>'Métricas Resultado de Base',
    'AttackVector'=>'Vector Ataque',
    'AttackComplexity'=>'Complejidad Ataque',
    'Authentication'=>'Autenticación',
    'ConfidentialityImpact'=>'Impacto Confidencialidad',
    'IntegrityImpact'=>'Impacto Integridad',
    'AvailabilityImpact'=>'Impacto Disponibilidad',
    'TemporalScoreMetrics'=>'Métricas Score Temporales',
    'Exploitability'=>'Explotabilidad',
    'RemediationLevel'=>'Nivel Remediación',
    'ReportConfidence'=>'Informe de Confianza',
    'EnvironmentalScoreMetrics'=>'Puntuación Medidas Medioambientales',
    'CollateralDamagePotential'=>'Posibles Daños Colaterales',
    'TargetDistribution'=>'Distribución Target',
    'ConfidentialityRequirement'=>'Requisito de Confidencialidad',
    'IntegrityRequirement'=>'Requisito Integridad',
    'AvailabilityRequirement'=>'Requisito Disponibilidad',
    'UpdateDREADScore'=>'Actualización DREAD Puntuación',
    'DamagePotential'=>'Posibles Daños',
    'Reproducibility'=>'Reproducibilidad',
    'AffectedUsers'=>'Los Usuarios Afectados',
    'Discoverability'=>'Discoverability',
    'UpdateOWASPScore'=>'Actualización de OWASP Puntuación',
    'ThreatAgentFactors'=>'Amenaza Factores del Agente',
    'SkillLevel'=>'Nivel de Habilidad',
    'Motive'=>'Motivo',
    'Opportunity'=>'Oportunidad',
    'Size'=>'Tamaño',
    'VulnerabilityFactors'=>'Factores de Vulnerabilidad',
    'EaseOfDiscovery'=>'Facilidad de Descubrimiento',
    'EaseOfExploit'=>'Facilidad de Exploit',
    'Awareness'=>'Conciencia',
    'IntrusionDetection'=>'Detección de Intrusos',
    'TechnicalImpact'=>'Impacto Técnico',
    'LossOfConfidentiality'=>'Pérdida de la Confidencialidad',
    'LossOfIntegrity'=>'Pérdida de la Integridad',
    'LossOfAvailability'=>'Pérdida de la Disponibilidad',
    'LossOfAccountability'=>'Pérdida de Rendición de Cuentas',
    'BusinessImpact'=>'Impacto en el Negocio',
    'FinancialDamage'=>'Daños Financiera',
    'ReputationDamage'=>'Reputación Daños',
    'NonCompliance'=>'Incumplimiento',
    'PrivacyViolation'=>'Violación de Privacidad',
    'UpdateCustomScore'=>'Puntuación de Actualización Personalizada',
    'ManuallyEnteredValue'=>'Valor Introducido Manualmente',
    'ScoreByClassic'=>'Puntuación por Classic',
    'ScoreByCVSS'=>'Puntuación por CVSS',
    'ScoreByDREAD'=>'Puntuación por DREAD',
    'ScoreByOWASP'=>'Puntuación por OWASP',
    'ScoreByCustom'=>'Puntuación de Custom',
    'BaseVector'=>'Vector Base',
    'TemporalVector'=>'Vector Temporal',
    'EnvironmentalVector'=>'Vector Ambiental',
    'SupportingDocumentation'=>'Documentación de Apoyo',
    'Import'=>'Importación',
    'Export'=>'Exportación',
    'ActivateTheImportExportExtra'=>'Activar la Importación / Exportación Extra',
    'PrintableView'=>'Vista Imprimible',
    'GroupBy'=>'Agrupar Por',
    'IncludedColumns'=>'Columnas Incluidas',
    'AllRisks'=>'Todo Riesgo',
    'DynamicRiskReport'=>'Informe de Riesgo Dinámico',
    'ObsoleteReports'=>'Informes Obsoletos',
    'Project'=>'Proyecto',
    'SortBy'=>'Ordenar Por',
    'MonthSubmitted'=>'Mes Enviado',
    'AssetManagement'=>'Gestión de Activos',
    'AutomatedDiscovery'=>'Automatizado Descubrimiento',
    'BatchImport'=>'Importación Por Lotes',
    'ManageAssets'=>'Administrar Activos',
    'AssetValuation'=>'Valoración de Activos',
    'AllowAccessToAssetManagementMenu'=>'Permitir acceso al menú "Gestión de Activos"',
    'AutomatedDiscoveryHelp'=>'Descubre todas las direcciones IP en vivo en el rango IP introducida. Direcciones IP en directo se añaden a la lista de activos. Los formatos aceptados:',
    'IPRange'=>'Rango de IP',
    'Search'=>'Búsqueda',
    'AddANewAsset'=>'Añadir un Nuevo Activo',
    'DeleteAnExistingAsset'=>'Eliminar un Activo Existente',
    'AssetName'=>'Nombre de Activos',
    'IPAddress'=>'Dirección IP',
    'AssetWasAddedSuccessfully'=>'Activos se ha agregado correctamente.',
    'AssetWasDeletedSuccessfully'=>'Activos se ha eliminado correctamente.',
    'ThereWasAProblemAddingTheAsset'=>'Hubo un problema al añadir el activo.',
    'ThereWasAProblemDeletingTheAsset'=>'Hubo un problema al eliminar el activo.',
    'ComingSoon'=>'Próximamente',
    'ExportRisks'=>'Riesgos de Exportación',
    'ExportMitigations'=>'Exportación Mitigaciones',
    'ExportReviews'=>'Comentarios de Exportación',
    'ExportCombined'=>'Exportación Combinada',
    'MitigatedBy'=>'Mitigado Por',
    'MitigationId'=>'Mitigación ID',
    'ReviewId'=>'Revisión ID',
    'AffectedAssets'=>'Activos Afectados',
    'Activate'=>'Activar',
    'DeleteRisks'=>'Eliminar Riesgos',
    'DeletedRisksCannotBeRecovered'=>'Riesgos Borrados No Se Pueden Recuperar Las',
    'RisksDeletedSuccessfully'=>'Riesgos Eliminado Correctamente',
    'ThereWasAProblemDeletingTheRisk'=>'Hubo Un Problema Eliminación De Los Riesgos',
    'Activated'=>'ACTIVADO',
    'IWantToReviewInsignificantRiskEvery'=>'Revisar cada Riesgo Muy Alto',
    'Insignificant'=>'Insignificante',
    'IConsiderVeryHighRiskToBeAnythingGreaterThan'=>'Considerar Riesgos Muy Altos que sean mayor que',
    'IConsiderHighRiskToBeLessThanAboveButGreaterThan'=>'Considerar Riesgos Altos menor que los anteriores, pero mayor que',
    'VeryHigh'=>'Muy Altos',
    'VeryHighRisk'=>'Riesgo Muy Alto',
    'IWantToReviewVeryHighRiskEvery'=> 'Revisar cada Riesgo Muy Alto',
    'AbleToReviewVeryHighRisks'=>'poder revisar los riesgos muy alto',
    'AbleToReviewInsignificantRisks'=>'poder revisar los riesgos insignificante',
    'TotalVeryHighRisks'=>'Total de Muy Altos Riesgos',
    'VeryHighRiskPercentage'=>'Porcentaje de Muy Alto Riesgo',
    'AllTeams'=>'Todos los Equipos',
    'FileUploadSettings'=>'Subir Archivo Ajustes',
    'AllowedFileTypes'=>'Tipos de archivo permitidos',
    'AddNewFileTypeOf'=>'Añadir nuevo tipo de archivo de',
    'DeleteCurrentFileTypeOf'=>'Eliminar tipo de archivo actual de',
    'MaximumUploadFileSize'=>'Tamaño Máximo de Carga de Archivos',
    'Bytes'=>'Bytes',
    'CheckAll'=>'Seleccionar Todos',
    'CheckAllRiskMgmt'=>'Seleccionar Todos Gestión de Riesgos',
    'CheckAllAssetMgmt'=>'Seleccionar Todos Gestión de Activos',
    'CheckAllConfigure'=>'Seleccionar Todos Configurar',
    'MitigationTeam'=>'Equipo del Mitigación',
    'ImportRisks'=>'Los Riesgos de Importación',
    'ImportAssets'=>'Activos de Importación',
    'AssetValue'=>'Valor Del Activo',
    'Register'=>'Registro',
    'RegisterSimpleRisk'=>'Registro SimpleRisk',
    'RegistrationText'=>'Al registrarse SimpleRisk se le proporciona su información de contacto para que pueda estar al día con la última información de la versión y notificaciones de seguridad importantes. Su información nunca será vendida a un tercero. Casos registrados también tienen la capacidad de realizar copias de seguridad y actualizado con el clic de un botón.',
    'RegistrationInformation'=>'Información De Registro',
    'Company'=>'Empresa',
    'JobTitle'=>'Título Profesional',
    'Phone'=>'Teléfono',
    'UpgradeSimpleRisk'=>'Actualiza SimpleRisk',
    'UpgradeInstructions'=>'En esta sección se utiliza el extra de actualización. Para asegurarse de que tiene la versión más reciente, seleccione "Actualizar", vuelva a registrar, y recarga esta página.',
    'NoUpgradeNeeded'=>'No se necesita una actualización en este momento.',
    'BackupDatabase'=>'Copia de Seguridad de la Base de Datos',
    'UpgradeApplication'=>'Actualiza la Aplicación',
    'UpgradeDatabase'=>'Actualizar la Base de Datos',
    'CustomExtras'=>'Extras Personalizados',
    'CustomExtrasText'=>'Sería fantástico si todo fuera libre, ¿no? Esperemos que la plataforma SimpleRisk núcleo es capaz de servir a todas sus necesidades de gestión de riesgos. Pero, si usted se encuentra todavía con ganas de más funcionalidad, que hemos desarrollado una serie de "Extras" que va a hacer precisamente eso por sólo unos cientos de dólares cada uno para una licencia perpetua.',
    'Upgrade'=>'Actualización',
    'Install'=>'Instalar',
    'Purchase'=>'Compra',
    'PasswordPolicy'=>'Directiva de Contraseñas',
    'MinimumNumberOfCharacters'=>'Número Mínimo de Caracteres',
    'RequireAlphaCharacter'=>'Requerir Carácter Alfa',
    'RequireUpperCaseCharacter'=>'Requerir Carácter Mayúscula',
    'RequireLowerCaseCharacter'=>'Requerir Carácter Minúscula',
    'RequireNumericCharacter'=>'Requerir Carácter Numérico',
    'RequireSpecialCharacter'=>'Requerir Carácter Especial',
    'Enabled'=>'Activado',
    'RiskPyramid'=>'Pirámide de Riesgo',
    'RiskPyramidDescription'=>'La pirámide de riesgo por encima de ayuda para mostrar la distribución de los riesgos entre los distintos niveles de riesgo. Una pirámide pesada superior puede ser una señal de que su organización está tomando demasiado riesgo.',
    'RiskAdvice'=>'Advertencia de Riesgo',
    ''=>'',
);

?>
