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
    'Home'=>'Hjem',
    'RiskManagement'=>'Risikostyring',
    'Reporting'=>'Rapportering',
    'Configure'=>'Konfigurere',
    'MyProfile'=>'Min Profil',
    'Logout'=>'Logg av',
    'LogInHere'=>'Logg inn på SimpleRisk',
    'Username'=>'Brukernavn',
    'Password'=>'Passord',
    'ForgotYourPassword'=>'Har du glemt passordet ditt',
    'Login'=>'Logg inn',
    'Reset'=>'Tilbakestille',
    'Send'=>'Sende',
    'Update'=>'Oppdatering',
    'SendPasswordResetEmail'=>'Send E-Post For Å Tilbakestille Passord',
    'PasswordReset'=>'For Å Tilbakestille Passord',
    'ResetToken'=>'Reset-Token',
    'RepeatPassword'=>'Gjenta Passord',
    'Submit'=>'Send inn',
    'ProfileDetails'=>'Profil Detaljer',
    'LastLogin'=>'Siste Innlogging',
    'ChangePassword'=>'Endre Passord',
    'CurrentPassword'=>'Nåværende Passord',
    'NewPassword'=>'Nytt Passord',
    'ConfirmPassword'=>'Bekreft Passord',
    'ConfigureRiskFormula'=>'Konfigurere Risiko Formel',
    'ConfigureReviewSettings'=>'Konfigurere Se Gjennom Innstillinger',
    'AddAndRemoveValues'=>'Legge til og Fjerne Verdier',
    'UserManagement'=>'Brukeradministrasjon',
    'RedefineNamingConventions'=>'Omdefinere Navnekonvensjoner',
    'AuditTrail'=>'Revisjonsspor',
    'Extras'=>'Tilbehør',
    'Announcements'=>'Kunngjøringer',
    'About'=>'Om',
    'Impact'=>'Innvirkning',
    'Likelihood'=>'Sannsynlighet',
    'MitigationEffort'=>'Avbøtende Innsats',
    'Change'=>'Endre',
    'to'=>'for å',
    'AddANewUser'=>'Legge til en Ny Bruker',
    'Type'=>'Type',
    'FullName'=>'Fullt Navn',
    'EmailAddress'=>'E-post Adresse',
    'Teams'=>'Team (e)',
    'ALL'=>'ALLE',
    'NONE'=>'INGEN',
    'UserResponsibilities'=>'Brukers Ansvar',
    'AbleToSubmitNewRisks'=>'I stand til å Sende Nye Risikoer',
    'AbleToModifyExistingRisks'=>'I stand til å Endre Eksisterende Risiko',
    'AbleToCloseRisks'=>'I stand til å Lukke Risiko',
    'AbleToPlanMitigations'=>'I stand til å Planlegge Tiltakene',
    'AbleToReviewLowRisks'=>'I stand til å skrive en Anmeldelse Lav Risiko',
    'AbleToReviewMediumRisks'=>'I stand til å skrive en Anmeldelse Middels Risiko',
    'AbleToReviewHighRisks'=>'I stand til å skrive en Anmeldelse Høy Risiko',
    'AllowAccessToConfigureMenu'=>'Du kan tillate Tilgang til "Konfigurere" - Menyen',
    'MultiFactorAuthentication'=>'Multi-Faktor Autentisering',
    'None'=>'Ingen',
    'Add'=>'Legg til',
    'ViewDetailsForUser'=>'Vise Detaljer for Brukeren',
    'DetailsForUser'=>'Detaljer for Brukeren',
    'Select'=>'Velg',
    'EnableAndDisableUsers'=>'Aktivere og Deaktivere Brukere',
    'EnableAndDisableUsersHelp'=>'Bruk denne funksjonen til å aktivere eller deaktivere brukeren logger og samtidig opprettholde revisjonsspor av brukerens aktiviteter',
    'DisableUser'=>'Deaktivere brukeren',
    'Disable'=>'Deaktivere',
    'EnableUser'=>'Aktivere brukeren',
    'Enable'=>'Aktiver',
    'DeleteAnExistingUser'=>'Slette en Eksisterende Bruker',
    'DeleteCurrentUser'=>'Slette gjeldende bruker',
    'Delete'=>'Slett',
    'SendPasswordResetEmailForUser'=>'Send e-post for å tilbakestille passord for brukeren',
    'Category'=>'Kategori',
    'AddNewCategoryNamed'=>'Legg til ny kategori som heter',
    'DeleteCurrentCategoryNamed'=>'Slette gjeldende kategori som heter',
    'Team'=>'Teamet',
    'AddNewTeamNamed'=>'Legg til ny gruppe som heter',
    'DeleteCurrentTeamNamed'=>'Slette gjeldende gruppe som heter',
    'Technology'=>'Teknologi',
    'AddNewTechnologyNamed'=>'Legg til ny teknologi som heter',
    'DeleteCurrentTechnologyNamed'=>'Slette gjeldende teknologi som heter',
    'SiteLocation'=>'Område/Sted',
    'AddNewSiteLocationNamed'=>'Legge til nye område/sted som heter',
    'DeleteCurrentSiteLocationNamed'=>'Slette gjeldende område/sted som heter',
    'ControlRegulation'=>'Kontroll Regulering',
    'AddNewControlRegulationNamed'=>'Legg til ny kontroll forskriften heter',
    'DeleteCurrentControlRegulationNamed'=>'Slette gjeldende kontroll forskriften heter',
    'RiskPlanningStrategy'=>'Risiko Planlegging Strategi',
    'AddNewRiskPlanningStrategyNamed'=>'Legge til nye risiko planlegging strategi heter',
    'DeleteCurrentRiskPlanningStrategyNamed'=>'Slette gjeldende risiko planlegging strategi heter',
    'CloseReason'=>'I Nærheten Grunn',
    'AddNewCloseReasonNamed'=>'Legg til ny nærheten grunn heter',
    'DeleteCurrentCloseReasonNamed'=>'Slette gjeldende i nærheten grunn heter',
    'IWantToReviewHighRiskEvery'=>'Jeg ønsker å gjennomgå HØY risiko hver',
    'IWantToReviewMediumRiskEvery'=>'Jeg ønsker å gjennomgå MIDDELS risiko hver',
    'IWantToReviewLowRiskEvery'=>'Jeg ønsker å gjennomgå LAV risiko hver',
    'days'=>'dager',
    'MyClassicRiskFormulaIs'=>'Min Klassiske Risiko Formelen Er',
    'RISK'=>'RISIKO',
    'IConsiderHighRiskToBeAnythingGreaterThan'=>'Jeg anser HØY risiko for å bli noe større enn',
    'IConsiderMediumRiskToBeLessThanAboveButGreaterThan'=>'Jeg anser MIDDELS risiko til å være mindre enn ovenfor, men større enn',
    'IConsiderlowRiskToBeLessThanAboveButGreaterThan'=>'Jeg anser LAV risiko for å bli mindre enn ovenfor, men større enn',
    'HighRisk'=>'Høy Risiko',
    'MediumRisk'=>'Middels Risiko',
    'LowRisk'=>'Lav Risiko',
    'Irrelevant'=>'Irrelevante',
    'SubmitYourRisks'=>'Sende risiko',
    'PlanYourMitigations'=>'Planlegge klimatiltak',
    'PerformManagementReviews'=>'Utføre vurderinger',
    'PrioritizeForProjectPlanning'=>'Planlegge prosjekter',
    'ReviewRisksRegularly'=>'Gjennom regelmessig',
    'DocumentANewRisk'=>'Dokumentet en Ny Risiko',
    'UseThisFormHelp'=>'Bruk dette skjemaet for å dokumentere en ny risiko for vurdering i risikostyringsprosessen',
    'Subject'=>'Emnet',
    'ExternalReferenceId'=>'Ekstern Referanse-ID',
    'ControlNumber'=>'Kontroll Antall',
    'Owner'=>'Eier',
    'OwnersManager'=>'Eierens Manager',
    'RiskScoringMethod'=>'Risiko Scoring Metoden',
    'CurrentLikelihood'=>'Gjeldende Sannsynligheten',
    'CurrentImpact'=>'Innvirkning',
    'RiskAssessment'=>'Risikovurdering',
    'AdditionalNotes'=>'Flere Merknader',
    'UNREVIEWED'=>'IKKE ER GJENNOMGÅTT',
    'PASTDUE'=>'FORFALT',
    'ID'=>'ID',
    'Status'=>'Status',
    'Risk'=>'Risiko',
    'DaysOpen'=>'Åpne Dager',
    'CalculatedRisk'=>'Kalkulert Risiko',
    'SubmittedBy'=>'Skrevet Av',
    'NextReviewDate'=>'Neste Gjennomgang Dato',
    'CVSSRiskScoring'=>'CVSS Risiko Scoring',
    'DREADRiskScoring'=>'GRUER Risiko Scoring',
    'OWASPRiskScoring'=>'OWASP Risiko Scoring',
    'CustomRiskScoring'=>'Tilpasset Risiko Scoring',
    'MitigationPlanningHelp'=>'Nedenfor er listen over innsendte risikoer som krever klimatiltak planlegging',
    'ManagementReviewHelp'=>'Nedenfor er listen over innsendte risikoer som krever ledelsens gjennomgang',
    'Submitted'=>'Innsendt',
    'MitigationPlanned'=>'Avbøtende Planlagt',
    'ManagementReview'=>'Gjennomgang',
    'No'=>'Ingen',
    'Yes'=>'Ja',
    'AddAndRemoveProjects'=>'Legge til og Fjerne Prosjekter',
    'AddAndRemoveProjectsHelp'=>'Legge til og fjerne prosjekter for å knytte flere risikoer sammen for prioritering',
    'AddNewProjectNamed'=>'Legge til nye prosjekt kalt',
    'DeleteCurrentProjectNamed'=>'Slette gjeldende prosjekt kalt',
    'AddUnassignedRisksToProjects'=>'Legg til Risiko som ikke er tilordnet til Prosjekter',
    'AddUnassignedRisksToProjectsHelp'=>'Dra og slipp ikke er tilordnet risiko merket for vederlag som et prosjekt inn i det aktuelle prosjektet kategorien',
    'PrioritizeProjects'=>'Prioritere Prosjekter',
    'PrioritizeProjectsHelp'=>'Flytt prosjekter rundt og endre rekkefølgen på prioritering. Når du er ferdig, ikke glem å trykke på "Oppdater" - knappen for å lagre endringene',
    'SaveRisksToProjects'=>'Lagre Risiko for Prosjekter',
    'RiskId'=>'Risiko-ID',
    'RiskActions'=>'Handlinger',
    'ReopenRisk'=>'Åpne Risiko',
    'CloseRisk'=>'I Nærheten Risiko',
    'EditRisk'=>'Rediger Risiko',
    'PlanAMitigation'=>'Planlegger du et Klimatiltak',
    'PerformAReview'=>'Utføre en Anmeldelse',
    'AddAComment'=>'Legg til en Kommentar',
    'ShowRiskScoringDetails'=>'Vis risiko Scoring detaljer',
    'HideRiskScoringDetails'=>'Skjul Risiko Scoring Detaljer',
    'Details'=>'Detaljer',
    'SubmissionDate'=>'Registreringsdato',
    'DateSubmitted'=>'Dato Sendt',
    'EditDetails'=>'Rediger Opplysninger',
    'Mitigation'=>'Klimatiltak',
    'MitigationDate'=>'Forminskingsmodulen innlevering dato',
    'PlanningStrategy'=>'Planlegging Strategi',
    'CurrentSolution'=>'Nåværende Løsning',
    'SecurityRequirements'=>'Krav Til Sikkerhet',
    'SecurityRecommendations'=>'Sikkerhet Anbefalinger',
    'EditMitigation'=>'Rediger Mitigation',
    'LastReview'=>'Siste Anmeldelse',
    'ReviewDate'=>'Gjennomgang Dato',
    'Reviewer'=>'Anmelder',
    'Review'=>'Anmeldelse',
    'NextStep'=>'Neste Trinn',
    'Comments'=>'Kommentarer',
    'ViewAllReviews'=>'Se Alle Anmeldelser',
    'ReviewHistory'=>'Gjennomgang Historie',
    'Comment'=>'Kommentar',
    'ClassicRiskScoring'=>'Klassisk Risiko Scoring',
    'RiskScoringActions'=>'Risiko Scoring Handlinger',
    'UpdateClassicScore'=>'Oppdatering Klassiske Score',
    'ScoreBy'=>'Resultat av',
    'RISKClassicExp1'=>'RISIKO = ( Sannsynlighet x Innvirkning + 2(Impact) )',
    'RISKClassicExp2'=>'RISIKO = ( Sannsynlighet x Innvirkning + Innvirkning )',
    'RISKClassicExp3'=>'RISIKO = ( Sannsynlighet x Innvirkning )',
    'RISKClassicExp4'=>'RISIKO = ( Sannsynlighet x Innvirkning + Sannsynligheten )',
    'RISKClassicExp5'=>'RISIKO = ( Sannsynlighet x Innvirkning + 2(Sannsynligheten) )',
    'Reason'=>'Årsak',
    'CloseOutInformation'=>'Close-Out Informasjon',
    'SubmitManagementReview'=>'Send Management Review',
    'SubmitRiskMitigation'=>'Send Risk Mitigation',
    'RiskDashboard'=>'Risiko-Oversikten',
    'RiskTrend'=>'Risiko Trend',
    'AllOpenRisksAssignedToMeByRiskLevel'=>'Alle åpne risikoer tildelt meg',
    'AllOpenRisksByRiskLevel'=>'Alle Åpne Risiko ved Risk Nivå',
    'AllOpenRisksConsideredForProjectsByRiskLevel'=>'Alle Åpne Risikoen Anses for Prosjekter av risikonivå',
    'AllOpenRisksAcceptedUntilNextReviewByRiskLevel'=>'Alle Åpne Risiko Akseptert Før Neste Gjennomgang av Risiko Nivå',
    'AllOpenRisksToSubmitAsAProductionIssueByRiskLevel'=>'Alle Åpne Risiko å Sende som en Produksjon Utgave av Risk Nivå',
    'AllOpenRisksByScoringMethod'=>'Alle Åpne Risiko ved å Score Metode',
    'AllClosedRisksByRiskLevel'=>'Alle Lukket Risiko ved Risk Nivå',
    'SubmittedRisksByDate'=>'Innsendt Risiko Etter Dato',
    'MitigationsByDate'=>'- Tiltakene Etter Dato',
    'ManagementReviewsByDate'=>'Ledelsen Anmeldelser Av Dato',
    'ProjectsAndRisksAssigned'=>'Prosjekter og Risiko Tildelt',
    'OpenRisks'=>'Åpne Risiko',
    'ClosedRisks'=>'Stengt Risiko',
    'ReportMyOpenHelp'=>'Denne rapporten viser alle åpne risikoer som har den aktuelle brukeren som eier eller leder forbundet med risiko bestilt av risikonivå',
    'ReportOpenHelp'=>'Denne rapporten viser alle åpne risiko bestilt av risikonivå',
    'ReportProjectsHelp'=>'Denne rapporten viser alle åpne risikoen anses for prosjekter bestilt av risikonivå',
    'ReportNextReviewHelp'=>'Denne rapporten viser alle åpne risiko akseptert før neste gjennomgang bestilt av risikonivå',
    'ReportProductionIssuesHelp'=>'Denne rapporten viser alle åpne risiko sendt inn som produksjon problemer bestilt av risikonivå',
    'ReportRiskScoringHelp'=>'Denne rapporten viser alle risiko scoring metoder og risikoen scoret bruker hver',
    'ReportClosedHelp'=>'Denne rapporten viser alle lukkede risiko bestilt av risikonivå',
    'ReportSubmittedByDateHelp'=>'Denne rapporten viser alle risikoer som er sortert etter dato for levering',
    'ReportMitigationsByDateHelp'=>'Denne rapporten viser alle tiltakene planlagt bestilt av avbøtende dato',
    'ReportMgmtReviewsByDateHelp'=>'Denne rapporten viser alle ledelse anmeldelser er sortert etter dato anmeldelse',
    'ReportProjectsAndRisksHelp'=>'Denne rapporten viser alle prosjekter og den risiko som er tilordnet til dem',
    'Language'=>'Språk',
    'AllOpenRisksNeedingReview'=>'Alle Åpne Risiko Behov for en Gjennomgang',
    'ReportReviewNeededHelp'=>'Denne rapporten viser alle åpne risiko som trenger en management review',
    'CustomValue'=>'Egendefinert Verdi',
    'ClosedRisksByDate'=>'Stengt Risiko Etter Dato',
    'DateClosed'=>'Dato Stengt',
    'ClosedBy'=>'Lukket Av',
    'ReportClosedByDateHelp'=>'Denne rapporten viser alle risikoer som er bestilt av nedleggelse dato',
    'AllOpenRisksByTeam'=>'Alle Åpne Risiko ved Lag',
    'ReportRiskTeamsHelp'=>'Denne rapporten viser alle lagene og de risikoene som er tilordnet til hver',
    'Unassigned'=>'Ikke tilordnet',
    'AllOpenRisksByTechnology'=>'Alle Åpne Risiko Av Teknologi',
    'ReportRiskTechnologiesHelp'=>'Denne rapporten viser alle teknologiene og den risiko som er tilordnet til hver',
    'RiskLevel'=>'Risikonivå',
    'BasedOnTheCurrentRiskScore'=>'Basert på poengsummen risiko, blir din neste gjennomgang. ',
    'WouldYouLikeToUseADifferentDate'=>'Ønsker du å bruke en annen dato i stedet?',
    'RisksOpenedAndClosedOverTime'=>'Risiko Åpnet og Lukket seg Over Tid',
    'AllRiskScoresAreAdjusted'=>'All risiko score er justert til å passe på en 0-10 skala.',
    'DetermineProjectStatus'=>'Bestemme Prosjekt Status',
    'ProjectStatusHelp'=>'Sted prosjekter i bøtter basert på deres nåværende status.',
    'ActiveProjects'=>'Aktive Prosjekter',
    'OnHoldProjects'=>'På Hold Prosjekter',
    'CompletedProjects'=>'Fullførte Prosjekter',
    'CancelledProjects'=>'Kansellerte Prosjekter',
    'UpdateProjectStatuses'=>'Oppdatering Prosjektet Statuser',
    'HighRiskReport'=>'Høy Risiko Rapport',
    'TotalOpenRisks'=>'Total Åpne Risiko',
    'TotalHighRisks'=>'Sum Høy Risiko',
    'HighRiskPercentage'=>'Høy Risiko Prosent',
    'UpdateClassicScore'=>'Oppdatere klassiske poeng',
    'CurrentLikelihood'=>'Gjeldende sannsynlighet',
    'CurrentImpact'=>'Nåværende innflytelse',   
    'UpdateCVSSScore'=>'Oppdatering CVSS Score',
    'BaseScoreMetrics'=>'Grunnpoengsummen Beregninger',
    'AttackVector'=>'Angrepsvektor.',
    'AttackComplexity'=>'Angrep Kompleksitet',
    'Authentication'=>'Godkjenning',
    'ConfidentialityImpact'=>'Konfidensialitet Innvirkning',
    'IntegrityImpact'=>'Integritet Innvirkning',
    'AvailabilityImpact'=>'Tilgjengelighet Innvirkning',
    'TemporalScoreMetrics'=>'Temporal Score Beregninger',
    'Exploitability'=>'Utnyttelsesgrad',
    'RemediationLevel'=>'Utbedring Nivå',
    'ReportConfidence'=>'Rapporten Tillit',
    'EnvironmentalScoreMetrics'=>'Miljø-Score Beregninger',
    'CollateralDamagePotential'=>'Utilsiktet Skade Potensial',
    'TargetDistribution'=>'Målet Distribusjon',
    'ConfidentialityRequirement'=>'Kravet Om Konfidensialitet',
    'IntegrityRequirement'=>'Integritet Krav',
    'AvailabilityRequirement'=>'Kravet Om Tilgjengelighet',
    'UpdateDREADScore'=>'Oppdatering GRUER Score',
    'DamagePotential'=>'Skade Potensial',
    'Reproducibility'=>'Reproduserbarheten',
    'AffectedUsers'=>'Berørte Brukere',
    'Discoverability'=>'Oversiktlighet',
    'UpdateOWASPScore'=>'Oppdatering OWASP Score',
    'ThreatAgentFactors'=>'Trusselen Agent Faktorer',
    'SkillLevel'=>'Nivå',
    'Motive'=>'Motiv',
    'Opportunity'=>'Mulighet',
    'Size'=>'Størrelse',
    'VulnerabilityFactors'=>'Sårbarhet Faktorer',
    'EaseOfDiscovery'=>'Enkel Discovery',
    'EaseOfExploit'=>'Enkel Utnytte',
    'Awareness'=>'Bevissthet',
    'IntrusionDetection'=>'Forstyrrelse Oppdagelsen',
    'TechnicalImpact'=>'Teknisk Effekt',
    'LossOfConfidentiality'=>'Tap av Konfidensialitet',
    'LossOfIntegrity'=>'Tap av Integritet',
    'LossOfAvailability'=>'Tap av Tilgjengelighet',
    'LossOfAccountability'=>'Tap av Ansvarlighet',
    'BusinessImpact'=>'Forretningslivet',
    'FinancialDamage'=>'Økonomisk Skade',
    'ReputationDamage'=>'Omdømme Skade',
    'NonCompliance'=>'Ikke-Samsvar',
    'PrivacyViolation'=>'Brudd På Personvern',
    'UpdateCustomScore'=>'Oppdatering Tilpasset Score',
    'ManuallyEnteredValue'=>'Manuelt Angitt Verdi',
    'ScoreByClassic'=>'Resultat av Klassisk',
    'ScoreByCVSS'=>'Resultat av CVSS',
    'ScoreByDREAD'=>'Resultat av FRYKT',
    'ScoreByOWASP'=>'Resultat av OWASP',
    'ScoreByCustom'=>'Resultat av Tilpasset',
    'BaseVector'=>'Base Vektor',
    'TemporalVector'=>'Temporal Vektor',
    'EnvironmentalVector'=>'Miljø-Vektor',
    'SupportingDocumentation'=>'Dokumentasjon',
    'Import'=>'Importere',
    'Export'=>'Eksport',
    'ActivateTheImportExportExtra'=>'Aktivere Import/Eksport Ekstra',
    'PrintableView'=>'Utskriftsvennlig Visning',
    'GroupBy'=>'Gruppen Av',
    'IncludedColumns'=>'Inkludert Kolonner',
    'AllRisks'=>'All Risiko',
    'DynamicRiskReport'=>'Dynamisk Risiko Rapport',
    'ObsoleteReports'=>'Foreldet Rapporter',
    'Project'=>'Prosjekt',
    'SortBy'=>'Sorter Etter',
    'MonthSubmitted'=>'Innsendt Måned',
    'AssetManagement'=>'Kapitalforvaltning',
    'AutomatedDiscovery'=>'Automatisk Oppdagelse',
    'BatchImport'=>'Satsvis Import',
    'ManageAssets'=>'Administrere Eiendeler',
    'AssetValuation'=>'Verdisetting',
    'AllowAccessToAssetManagementMenu'=>'Du kan tillate Tilgang til "Asset Management" - Menyen',
    'AutomatedDiscoveryHelp'=>'Oppdag alt live IP-adresser som er skrevet inn IP-område. Live IP-adresser vil bli lagt til ressurs-listen. Akseptabelt formater:',
    'IPRange'=>'IP-Område',
    'Search'=>'Søk',
    'AddANewAsset'=>'Legge til en Ny Ressurs',
    'DeleteAnExistingAsset'=>'Slette en Eksisterende Ressurs',
    'AssetName'=>'Asset Navn',
    'IPAddress'=>'IP-Adressen',
    'AssetWasAddedSuccessfully'=>'Aktiva var lagt til.',
    'AssetWasDeletedSuccessfully'=>'Aktiva var slettet.',
    'ThereWasAProblemAddingTheAsset'=>'Det var et problem med å legge til eiendelen.',
    'ThereWasAProblemDeletingTheAsset'=>'Det var et problem å slette eiendelen.',
    'ComingSoon'=>'Kommer Snart',
    'ExportRisks'=>'Eksport Risiko',
    'ExportMitigations'=>'Eksport Migreringer',
    'ExportReviews'=>'Eksport Anmeldelser',
    'ExportCombined'=>'Eksport Kombinert',
    'MitigatedBy'=>'Motvirket Av',
    'MitigationId'=>'Avbøtende ID',
    'ReviewId'=>'Gjennomgå ID',
    'AffectedAssets'=>'Påvirket Eiendeler',
    'Activate'=>'Aktiver',
    'DeleteRisks'=>'Slett Risiko',
    'DeletedRisksCannotBeRecovered'=>'Slettet Risiko, Kan Ikke Gjenopprettes',
    'RisksDeletedSuccessfully'=>'Risiko(s) er Slettet',
    'ThereWasAProblemDeletingTheRisk'=>'Det Var et Problem å Slette Risiko(s)',
    'Activated'=>'AKTIVERT',
    'IWantToReviewInsignificantRiskEvery'=>'Jeg ønsker å gjennomgå UBETYDELIG risiko hver',
    'Insignificant'=>'Ubetydelig',
    'IConsiderVeryHighRiskToBeAnythingGreaterThan'=>'Jeg anser SVÆRT HØY risiko for å være noe større enn',
    'IConsiderHighRiskToBeLessThanAboveButGreaterThan'=>'Jeg anser HØY risiko for å være mindre enn ovenfor, men større enn',
    'VeryHigh'=>'Svært Høy',
    'VeryHighRisk'=>'Svært Høy Risiko',
    'IWantToReviewVeryHighRiskEvery'=> 'Jeg ønsker å gjennomgå SVÆRT HØY risiko for hver',
    'AbleToReviewVeryHighRisks'=>'I stand til å skrive en Anmeldelse Svært Høy Risiko',
    'AbleToReviewInsignificantRisks'=>'I stand til å skrive en Anmeldelse Ubetydelig Risiko',
    'TotalVeryHighRisks'=>'Total Svært Høy Risiko',
    'VeryHighRiskPercentage'=>'Svært Høy Risiko Prosent',
    'AllTeams'=>'Alle Lag',
    'FileUploadSettings'=>'Opplasting Av Fil-Innstillinger',
    'AllowedFileTypes'=>'Tillatt Filtyper',
    'AddNewFileTypeOf'=>'Legg til ny fil, type',
    'DeleteCurrentFileTypeOf'=>'Slette gjeldende fil type',
    'MaximumUploadFileSize'=>'Maksimal Last Opp Fil Størrelse',
    'Bytes'=>'Byte',
    'CheckAll'=>'Sjekk Alle',
    'CheckAllRiskMgmt'=>'Sjekk Alle Risikostyring',
    'CheckAllAssetMgmt'=>'Sjekk Alle Asset Management',
    'CheckAllConfigure'=>'Sjekk Alle Konfigurere',
    'MitigationTeam'=>'Avbøtende Team',
    'ImportRisks'=>'Import Risiko',
    'ImportAssets'=>'Import Eiendeler',
    'AssetValue'=>'Aktivaverdi',
    'Register'=>'Registrer deg',
    'RegisterSimpleRisk'=>'Registrer SimpleRisk',
    'RegistrationText'=>'Ved å registrere SimpleRisk vil du gi din kontaktinformasjon slik at du kan bli oppdatert med den nyeste utgaven informasjon og viktige sikkerhetsmeldinger. Din informasjon vil aldri bli solgt til en tredjepart. Registrert tilfeller også ha muligheten til å bli støttet opp og oppgradert med å klikke på en knapp.',
    'RegistrationInformation'=>'Registrering Informasjon',
    'Company'=>'Selskapet',
    'JobTitle'=>'Tittel',
    'Phone'=>'Telefon',
    'UpgradeSimpleRisk'=>'Oppgradering SimpleRisk',
    'UpgradeInstructions'=>'Denne delen bruker Oppgradere Ekstra. For å sikre at du har den nyeste versjonen, velg "Update", re-registrere og laste denne siden.',
    'NoUpgradeNeeded'=>'Ingen oppgradering er nødvendig på denne tiden.',
    'BackupDatabase'=>'Backup av Databasen',
    'UpgradeApplication'=>'Oppgradere Programmet',
    'UpgradeDatabase'=>'Oppgradere Databasen',
    'CustomExtras'=>'Tilpasset Tilbehør',
    'CustomExtrasText'=>'Det ville være fantastisk hvis alt var gratis, ikke sant? Forhåpentligvis kjernen SimpleRisk plattformen er i stand til å betjene alle dine risikostyring behov. Men, hvis du finner deg selv fortsatt ønsker mer funksjonalitet, har vi utviklet en serie av "Extras" som vil gjøre nettopp det.',
    'Upgrade'=>'Oppgradering',
    'Install'=>'Installere',
    'Purchase'=>'Kjøp',
    'PasswordPolicy'=>'Passord Policy',
    'MinimumNumberOfCharacters'=>'Minimum Antall Tegn',
    'RequireAlphaCharacter'=>'Krever Alfategn',
    'RequireUpperCaseCharacter'=>'Krever Stor Bokstav',
    'RequireLowerCaseCharacter'=>'Krever Små Bokstaver',
    'RequireNumericCharacter'=>'Krever Numeriske Tegn',
    'RequireSpecialCharacter'=>'Kreve Spesiell Karakter',
    'Enabled'=>'Aktivert',
    'RiskPyramid'=>'Risiko Pyramide',
    'RiskPyramidDescription'=>'Risikoen pyramiden ovenfor bidrar til å vise fordelingen av risiko mellom ulike risikonivå. En topptung pyramide kan være et tegn på at organisasjonen er å ta på seg for mye risiko.',
    'RiskAdvice'=>'Risiko Råd',
    'AddDeleteAssets'=>'Legge Til Og Slette Eiendeler',
    'EditAssets'=>'Rediger Eiendeler',
    'AutomaticAssetValuation'=>'Automatisk Verdisetting',
    'ManualAssetValuation'=>'Manuell Verdisetting',
    'MinimumValue'=>'Minimum Verdi',
    'MaximumValue'=>'Maksimal Verdi',
    'ValueRange'=>'Verdi Utvalg',
    'DefaultAssetValuation'=>'Standard Verdisetting',
    'Default'=>'Standard',
    'RisksAndAssets'=>'Risiko og Eiendeler',
    'Report'=>'Rapport',
    'RisksByAsset'=>'Risiko av anleggsmidler',
    'AssetsByRisk'=>'Eiendeler med Risiko',
    'MaximumQuantitativeLoss'=>'Maksimal Kvantitative Tap',
    'MitigationOwner'=>'Avbøtende Eier',
    'MitigationCost'=>'Avbøtende Kostnad',
    'RiskColumns'=>'Risiko Kolonner',
    'MitigationColumns'=>'Avbøtende Kolonner',
    'ReviewColumns'=>'Gjennomgang Kolonner',
    'ChangeStatus'=>'Endre Status',
    'SetRiskStatusTo'=>'Sett Status Til Risiko',
    'AddNewStatusNamed'=>'Legge til nye status som heter',
    'DeleteStatusNamed'=>'Slette status som heter',
    'DefaultCurrencySymbol'=>'Standard Valutasymbol',
    'DefaultValues'=>'Standard Verdier',
    'RiskSource'=>'Risiko Kilde',
    'AddNewSourceNamed'=>'Legg til ny navngitt kilde',
    'DeleteSourceNamed'=>'Slett navngitt kilde',
    'CheckAllAssessments'=>'Sjekk Alle Vurderinger',
    'AllowAccessToAssessmentsMenu'=>'Du kan tillate Tilgang til "Vurderinger" - Menyen',
    'Assessments'=>'Vurderinger',
    'AvailableAssessments'=>'Tilgjengelig Vurderinger',
    'PendingRisks'=>'I Påvente Risiko',
    'CreateAssessment'=>'Opprette Vurdering',
    'EditAssessment'=>'Rediger Vurdering',
    'Overview'=>'Oversikt',
    'OpenVsClosed'=>'Åpne vs Lukkede',
    'MitigatedVsUnmitigated'=>'Redusert vs Rendyrket',
    'ReviewedVsUnreviewed'=>'Anmeldt vs Unreviewed',
    'OpenedRisks'=>'Åpnet Risiko',
    'MailSettings'=>'Post-Innstillinger',
    'TransportAgent'=>'Transportagent',
    'FromName'=>'Fra Navn',
    'FromEmail'=>'Fra E-Post',
    'ReplyToName'=>'ReplyTo Navn',
    'ReplyToEmail'=>'ReplyTo E-Post',
    'Host'=>'Vert',
    'SMTPAuthentication'=>'SMTP-Autentisering',
    'Encryption'=>'Kryptering',
    'Port'=>'Port',
    'Next'=>'Neste',
    'NewAssessmentQuestion'=>'Ny Vurdering Spørsmålet',
    'Question'=>'Spørsmål',
    'RiskScore'=>'Risiko Score',
    'SubmitRisk'=>'Send Risiko',
    'Answer'=>'Svar',
    'AddQuestion'=>'Legg Til Spørsmål',
    'SaveAssessment'=>'Lagre Vurdering',
    'SendAssessment'=>'Send Vurdering',
    'DeleteAssessment'=>'Slette Vurdering',
    'AssessmentName'=>'Vurdering Navn',
    'SendTo'=>'Send Til',
    'ActiveAssessments'=>'Aktiv Vurderinger',
    'SentTo'=>'Sendt Til',
    'From'=>'Fra',
    'Key'=>'Nøkkel',
    'GoToSSOLoginPage'=>'Gå til SSO påloggingssiden',
    'APIKey'=>'API-nøkkel',
    'GenerateAPIKey'=>'Generer API-nøkkel',
    'RotateAPIKey'=>'Roter API-nøkkel',
    'InvalidateAPIKey'=>'Oppheve API-nøkkel',
    'Deactivate'=>'Deaktivere',
    'ImportExportExtra'=>'Import / eksport ekstra',
    'SaveDetails'=>'Lagre detaljer',
    'ClearForm'=>'Tøm skjema',
    'SaveMitigation'=>'Lagre klimatiltak',
    'Cancel'=>'Avbryt',
    'SubmitReview'=>'Sende gjennomgang',
    'UnassignedRisks'=>'Ikke tilordnede risikoer',
    'DisableRegistrationNotice'=>'Deaktiver registrering varsel',
    'UserPolicy'=>'Brukerpolicy',
    'UseCaseSensitiveValidationOfUsername'=>'Bruke sakssensitivt godkjenningen av brukernavn',
    'MitigationPlanning'=>'Planlagte avbøtende dato',
    'AssetDetails'=>'Aktivadetaljer',
    'RiskList'=>'Risikolisten',
    'Are you sure you want to close the risk? All changes will be lost!'=>'Er du sikker på at du vil lukke risikoen? Alle endringer vil gå tapt!',
    'MinimumPasswordAge' => 'Minimum passordalder',
    'MaximumPasswordAge' => 'Maksimal passordalder',
    'MaximumAttemptsLockout' => 'Maksimal forsøk låsing',
    'MaximumAttemptsLockoutTime' => 'Maksimal forsøk Lockout tid',
    'attempts' => 'forsøk',
    'minutes' => 'minutter',
    'AccountLockedOut' => 'Konto låst',
    'AccountLockoutPolicy' => 'Policy for låsing av konto',
    'ImportExportIsDeactivated' => 'Import / eksport er deaktivert',
    'PurchaseTheExtra' => 'Kjøpe ekstra',
    'ExpandAll' => 'Utvide alle',
    'ConditionMessageForMinChar' => 'Passord bør inneholde minst $min_chars tegn.',
    'ConditionMessageForAlpha' => 'Passord bør inneholde en bokstaver.',
    'ConditionMessageForUppercase' => 'Passord bør inneholde et store tegn.',
    'ConditionMessageForLowercase' => 'Passord bør inneholde et små tegn.',
    'ConditionMessageForDigit' => 'Passord må inneholde et siffer.',
    'ConditionMessageForSpecialchar' => 'Passord bør inneholde spesialtegn.',
    'ConditionMessageForMinPasswordAge' => 'Passord kan oppdateres fra sist oppdatert tid minst $min_password_age dager senere.',
    'TrustedDomains' => 'Klarerte domener',
    'SimpleRiskColumnMapping' => 'SimpleRisk Kolonnetilordning',
    'Mapping' => 'Kartlegging',
    'Optional' => 'Valgfritt',
    'SaveMappingAs' => 'Lagre tilordning som',
    'EncryptionLevel' => 'Krypteringsnivået',
    'Level' => 'Nivå',
    'Description' => 'Beskrivelse',
    'ShowRiskScoreOverTime' => 'Vis risiko Score Over tid',
    'HideRiskScoreOverTime' => 'Skjule risiko Score Over tid',
    'ReviewRegularlyHelp' => 'Nedenfor er listen over alle risikoer sortert etter Unreviewed, siste grunn og neste gjennomgang.',
    'RiskScoringHistory' => 'Risiko Scoring historie',
    'RiskAddPermissionMessage' => 'Du har ikke tillatelse til å sende nye risikoer.  Risikoen du forsøker å sende vil ikke bli registrert.  Vennligst kontakt Administrator hvis du føler at du har fått denne meldingen ved en feil.',
    'SubjectRiskCannotBeEmpty' => 'Gjenstand for en risiko kan ikke være tomt',
    'InvalidRiskID' => 'Ugyldig risiko-ID',
    'Success' => 'Suksess',
    'RiskUpdatePermissionMessage' => 'Du har ikke tillatelse til å endre risiko.  Noen risiko som du prøver å endre vil ikke bli registrert.  Vennligst kontakt Administrator hvis du føler at du har fått denne meldingen ved en feil.',
    'RiskReviewPermission' => 'Du har ikke tillatelse til å se gjennom $risk_level nivå risikoen.  Noen vurderinger du forsøker å sende vil ikke bli registrert.  Vennligst kontakt administrator hvis du føler at du har fått denne meldingen ved en feil.',
    'DateAndTime' => 'Dato og klokkeslett',
    'mCryptWarning' => 'Forlengelsen "mcrypt" må installeres for kryptering ekstra skal fungere.  Installer den fortsetter.',
    'APIInCompatibleWithEncryptionLevel' => 'API er ikke kompatibel med brukeren krypteringsnivået for ekstra for kryptert Database.',
    'UnauthenticatedAccessInAPI' => 'Uautorisert tilgang.  Vennligst logg inn eller levere en nøkkel for å bruke APIEN SimpleRisk.',
    'ResetPasswordMessageInUserLevelEncryption' => 'Du kan ikke tilbakestille passordet fordi denne brukeren er i brukernivå kryptering. Kontakt systemansvarlig.',
    'YouNeedToSpecifyAnIdParameter' => 'Du må angi en id-parameteren.',
    'NoMitigation' => 'Det er ingen angitt begrensning.',
    'NoReview' => 'Det er ingen angitte gjennomgang.',
    'MysqldumpPathWasSavedSuccessfully'=>'Mysqldump banen ble lagret.',
    'UnavailableMysqldumpService'=>'Det finnes ingen avaiable \'mysqldump\' tjeneste på serveren. Angi absolutt mysqldump service sti.',
    'AllOpenRisksByTeamByLevel'=>'Alle åpne risiko av Team av risikonivået',
    '' => '',
);

?>
