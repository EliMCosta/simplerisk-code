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
    'Home'=>'בבית',
    'RiskManagement'=>'ניהול סיכונים',
    'Reporting'=>'דיווח',
    'Configure'=>'להגדיר',
    'MyProfile'=>'הפרופיל שלי',
    'Logout'=>'ניתוק',
    'LogInHere'=>'כניסה SimpleRisk',
    'Username'=>'שם משתמש',
    'Password'=>'הסיסמה',
    'ForgotYourPassword'=>'שכחתי את הסיסמה שלך',
    'Login'=>'כניסה',
    'Reset'=>'איפוס',
    'Send'=>'שלח',
    'Update'=>'עדכון',
    'SendPasswordResetEmail'=>'לשלוח את הדוא " ל לאיפוס הסיסמה',
    'PasswordReset'=>'איפוס הסיסמה',
    'ResetToken'=>'איפוס אסימון',
    'RepeatPassword'=>'חזור על הסיסמה',
    'Submit'=>'להגיש',
    'ProfileDetails'=>'פרטי הפרופיל',
    'LastLogin'=>'הכניסה האחרונה',
    'ChangePassword'=>'שינוי סיסמה',
    'CurrentPassword'=>'הסיסמה הנוכחית',
    'NewPassword'=>'הסיסמה החדשה',
    'ConfirmPassword'=>'אישור סיסמה',
    'ConfigureRiskFormula'=>'הגדרת הסיכון פורמולה',
    'ConfigureReviewSettings'=>'להגדיר סקירת הגדרות',
    'AddAndRemoveValues'=>'הוספה והסרה של ערכים',
    'UserManagement'=>'ניהול משתמשים',
    'RedefineNamingConventions'=>'להגדיר מחדש מוסכמות למתן שמות',
    'AuditTrail'=>'נתיב ביקורת',
    'Extras'=>'תוספות',
    'Announcements'=>'הודעות',
    'About'=>'על',
    'Impact'=>'ההשפעה',
    'Likelihood'=>'הסבירות',
    'MitigationEffort'=>'הפחתת המאמץ',
    'Change'=>'שינוי',
    'to'=>'כדי',
    'AddANewUser'=>'הוספת משתמש חדש',
    'Type'=>'סוג',
    'FullName'=>'שם מלא',
    'EmailAddress'=>'כתובת דואר אלקטרוני',
    'Teams'=>'צוות(s)',
    'ALL'=>'כל',
    'NONE'=>'אף אחד',
    'UserResponsibilities'=>'אחריות המשתמש',
    'AbleToSubmitNewRisks'=>'יכולים להגיש סיכונים חדשים',
    'AbleToModifyExistingRisks'=>'מסוגל לשנות קיימים סיכונים',
    'AbleToCloseRisks'=>'מסוגל לסגור סיכונים',
    'AbleToPlanMitigations'=>'מסוגל לתכנן בהקלות',
    'AbleToReviewLowRisks'=>'תוכל לסקור נמוך סיכונים',
    'AbleToReviewMediumRisks'=>'מסוגל לבחון בינוני סיכונים',
    'AbleToReviewHighRisks'=>'מסוגל לבחון סיכונים גבוהים',
    'AllowAccessToConfigureMenu'=>'לאפשר גישה כדי "להגדיר" תפריט',
    'MultiFactorAuthentication'=>'אימות מרובה גורמים',
    'None'=>'אף אחד',
    'Add'=>'להוסיף',
    'ViewDetailsForUser'=>'הצגת פרטים עבור המשתמש',
    'DetailsForUser'=>'פרטים על המשתמש',
    'Select'=>'בחר',
    'EnableAndDisableUsers'=>'להפעיל או להשבית משתמשים',
    'EnableAndDisableUsersHelp'=>'השתמש בתכונה זו כדי להפעיל או להשבית את כניסות המשתמש תוך שמירה על הביקורת עקבות של המשתמש פעילויות',
    'DisableUser'=>'בטל את המשתמש',
    'Disable'=>'השבת',
    'EnableUser'=>'לאפשר למשתמש',
    'Enable'=>'לאפשר',
    'DeleteAnExistingUser'=>'מחיקת משתמש קיים',
    'DeleteCurrentUser'=>'למחוק את המשתמש הנוכחי',
    'Delete'=>'למחוק',
    'SendPasswordResetEmailForUser'=>'לשלוח את הדוא " ל לאיפוס הסיסמה עבור המשתמש',
    'Category'=>'קטגוריה',
    'AddNewCategoryNamed'=>'להוסיף קטגוריה חדשה בשם',
    'DeleteCurrentCategoryNamed'=>'מחיקת הנוכחי קטגוריה בשם',
    'Team'=>'צוות',
    'AddNewTeamNamed'=>'הוסף קבוצה חדשה בשם',
    'DeleteCurrentTeamNamed'=>'מחיקת הנוכחי הקבוצה בשם.',
    'Technology'=>'טכנולוגיה',
    'AddNewTechnologyNamed'=>'הוספת טכנולוגיה חדשה בשם',
    'DeleteCurrentTechnologyNamed'=>'למחוק את הטכנולוגיה העדכנית בשם',
    'SiteLocation'=>'אתר/מיקום',
    'AddNewSiteLocationNamed'=>'הוסף אתר/מיקום בשם',
    'DeleteCurrentSiteLocationNamed'=>'למחוק את האתר הנוכחי/מיקום בשם',
    'ControlRegulation'=>'שליטה תקנה',
    'AddNewControlRegulationNamed'=>'הוסף שליטה תקנה בשם',
    'DeleteCurrentControlRegulationNamed'=>'מחיקת הנוכחי שליטה תקנה בשם',
    'RiskPlanningStrategy'=>'הסיכון תכנון אסטרטגיה',
    'AddNewRiskPlanningStrategyNamed'=>'הוסף הסיכון תכנון אסטרטגיה בשם',
    'DeleteCurrentRiskPlanningStrategyNamed'=>'מחיקת הנוכחי הסיכון תכנון אסטרטגיה בשם',
    'CloseReason'=>'קרוב סיבה',
    'AddNewCloseReasonNamed'=>'הוסף קרוב סיבה בשם',
    'DeleteCurrentCloseReasonNamed'=>'מחיקת הנוכחי קרוב סיבה בשם',
    'IWantToReviewHighRiskEvery'=>'אני רוצה לבחון את סיכון גבוה כל',
    'IWantToReviewMediumRiskEvery'=>'אני רוצה לבחון את סיכון בינוני כל',
    'IWantToReviewLowRiskEvery'=>'אני רוצה לבחון את סיכון נמוך כל',
    'days'=>'ימים',
    'MyClassicRiskFormulaIs'=>'הקלאסי שלי הסיכון הנוסחה',
    'RISK'=>'הסיכון',
    'IConsiderHighRiskToBeAnythingGreaterThan'=>'אני מחשיב את סיכון גבוה להיות משהו גדול יותר',
    'IConsiderMediumRiskToBeLessThanAboveButGreaterThan'=>'אני מחשיב את סיכון בינוני להיות פחות לעיל, אבל גדול יותר',
    'IConsiderlowRiskToBeLessThanAboveButGreaterThan'=>'אני מחשיב את סיכון נמוך להיות פחות לעיל, אבל גדול יותר',
    'HighRisk'=>'סיכון גבוה',
    'MediumRisk'=>'סיכון בינוני',
    'LowRisk'=>'סיכון נמוך',
    'Irrelevant'=>'לא רלוונטי',
    'SubmitYourRisks'=>'שלח את הסיכון',
    'PlanYourMitigations'=>'תוכנית להפחתת הסיכון',
    'PerformManagementReviews'=>'המבצעים ביקורות',
    'PrioritizeForProjectPlanning'=>'לתכנון פרוייקטים',
    'ReviewRisksRegularly'=>'סקור בקביעות',
    'DocumentANewRisk'=>'מסמך סיכון חדשה',
    'UseThisFormHelp'=>'השתמש בטופס זה על מנת לתעד סיכון חדשה על שיקול ניהול סיכונים תהליך',
    'Subject'=>'הנושא',
    'ExternalReferenceId'=>'הפניה חיצונית ID',
    'ControlNumber'=>'מספר בקרה',
    'Owner'=>'הבעלים',
    'OwnersManager'=>'המנהל של הבעלים',
    'RiskScoringMethod'=>'הסיכון שיטת ניקוד',
    'CurrentLikelihood'=>'הנוכחי הסבירות',
    'CurrentImpact'=>'הנוכחי את ההשפעה.',
    'RiskAssessment'=>'הערכת סיכונים',
    'AdditionalNotes'=>'הערות נוספות',
    'UNREVIEWED'=>'UNREVIEWED',
    'PASTDUE'=>'בשל העבר',
    'ID'=>'מזהה',
    'Status'=>'סטטוס',
    'Risk'=>'הסיכון',
    'DaysOpen'=>'ימים פתוחים',
    'CalculatedRisk'=>'סיכון מחושב',
    'SubmittedBy'=>'שהוגש על ידי',
    'NextReviewDate'=>'הסקירה הבאה תאריך',
    'CVSSRiskScoring'=>'CVSS הסיכון ניקוד',
    'DREADRiskScoring'=>'חרדה הסיכון ניקוד',
    'OWASPRiskScoring'=>'OWASP הסיכון ניקוד',
    'CustomRiskScoring'=>'מותאם אישית הסיכון ניקוד',
    'MitigationPlanningHelp'=>'להלן רשימת שהוגשו סיכונים הדורשים תכנון מיתון',
    'ManagementReviewHelp'=>'להלן רשימת שהוגשו סיכונים הדורשים ניהול הביקורת',
    'Submitted'=>'שהוגשו',
    'MitigationPlanned'=>'הקלה המתוכנן',
    'ManagementReview'=>'ניהול ביקורת',
    'No'=>'לא',
    'Yes'=>'כן',
    'AddAndRemoveProjects'=>'הוספה והסרה של פרויקטים',
    'AddAndRemoveProjectsHelp'=>'הוספה והסרה של פרויקטים על מנת לשייך מספר סיכונים יחד תעדוף',
    'AddNewProjectNamed'=>'הוספת פרויקט חדש בשם',
    'DeleteCurrentProjectNamed'=>'למחוק את הפרויקט הנוכחי בשם',
    'AddUnassignedRisksToProjects'=>'להוסיף משימה סיכונים בפרויקטים',
    'AddUnassignedRisksToProjectsHelp'=>'גרור ושחרר ב הסיכונים מסומן על שיקול כפרויקט לתוך המתאים פרויקט טאב',
    'PrioritizeProjects'=>'לתעדף פרויקטים',
    'PrioritizeProjectsHelp'=>'להזיז פרויקטים סביב שינוי סדר עדיפויות. ברגע שסיים, לא לשכוח ללחוץ על "עדכן" על מנת לשמור את השינויים',
    'SaveRisksToProjects'=>'להציל את הסיכונים בפרויקטים',
    'RiskId'=>'הסיכון ID',
    'RiskActions'=>'פעולות',
    'ReopenRisk'=>'לפתוח מחדש את הסיכון',
    'CloseRisk'=>'קרוב סיכון',
    'EditRisk'=>'עריכה סיכון',
    'PlanAMitigation'=>'תכנית להפחתת הסיכון.',
    'PerformAReview'=>'לבצע סקירה',
    'AddAComment'=>'הוסף תגובה',
    'ShowRiskScoringDetails'=>'הסיכון תצוגה הבקיע פרטים',
    'HideRiskScoringDetails'=>'להסתיר את הסיכון ניקוד פרטים',
    'Details'=>'פרטים',
    'SubmissionDate'=>'מועד ההגשה',
    'DateSubmitted'=>'תאריך שהוגשו',
    'EditDetails'=>'עריכת פרטים',
    'Mitigation'=>'מיתון',
    'MitigationDate'=>'תאריך שליחה להפחתת הסיכון',
    'PlanningStrategy'=>'תכנון אסטרטגיה',
    'CurrentSolution'=>'פתרון הנוכחי',
    'SecurityRequirements'=>'דרישות אבטחה',
    'SecurityRecommendations'=>'המלצות אבטחה',
    'EditMitigation'=>'עריכה מיתון',
    'LastReview'=>'סקירה אחרונה',
    'ReviewDate'=>'תאריך ביקורת',
    'Reviewer'=>'המבקר',
    'Review'=>'סקירה',
    'NextStep'=>'הצעד הבא',
    'Comments'=>'תגובות',
    'ViewAllReviews'=>'הצג את כל ביקורות',
    'ReviewHistory'=>'סקירה היסטורית',
    'Comment'=>'תגובה',
    'ClassicRiskScoring'=>'קלאסי הסיכון ניקוד',
    'RiskScoringActions'=>'הסיכון ניקוד פעולות',
    'UpdateClassicScore'=>'עדכון קלאסי ציון',
    'ScoreBy'=>'ציון על ידי',
    'RISKClassicExp1'=>'סיכון = ( הסבירות x השפעה + 2(השפעה) )',
    'RISKClassicExp2'=>'סיכון = ( הסבירות x השפעה + השפעה )',
    'RISKClassicExp3'=>'סיכון = ( הסבירות x השפעה )',
    'RISKClassicExp4'=>'סיכון = ( הסבירות x השפעה + הסבירות )',
    'RISKClassicExp5'=>'סיכון = ( הסבירות x השפעה + 2(הסבירות) )',
    'Reason'=>'סיבה',
    'CloseOutInformation'=>'קרוב-מידע',
    'SubmitManagementReview'=>'להגיש ניהול הביקורת',
    'SubmitRiskMitigation'=>'להגיש סיכון להפחתת הסיכון',
    'RiskDashboard'=>'הסיכון המחוונים.',
    'RiskTrend'=>'סיכון מגמה',
    'AllOpenRisksAssignedToMeByRiskLevel'=>'הכל פתוח סיכונים ששייכים לי',
    'AllOpenRisksByRiskLevel'=>'כל פתח סיכונים על ידי רמת הסיכון',
    'AllOpenRisksConsideredForProjectsByRiskLevel'=>'כל פתח סיכונים נחשב עבור פרויקטים לפי רמת הסיכון',
    'AllOpenRisksAcceptedUntilNextReviewByRiskLevel'=>'כל פתח סיכונים מקובל עד הביקורת הבאה על-ידי רמת הסיכון',
    'AllOpenRisksToSubmitAsAProductionIssueByRiskLevel'=>'כל פתח סיכונים לשלוח כל הפקה הבעיה על-ידי רמת הסיכון',
    'AllOpenRisksByScoringMethod'=>'כל פתח סיכונים על ידי שיטת הניקוד',
    'AllClosedRisksByRiskLevel'=>'הכל סגור סיכונים על ידי רמת הסיכון',
    'SubmittedRisksByDate'=>'הוגש סיכונים לפי תאריך',
    'MitigationsByDate'=>'גורמים מקלים לפי תאריך',
    'ManagementReviewsByDate'=>'ניהול חוות דעת עד תאריך',
    'ProjectsAndRisksAssigned'=>'הפרויקטים והסיכונים מוקצה',
    'OpenRisks'=>'פתח סיכונים',
    'ClosedRisks'=>'סגור סיכונים',
    'ReportMyOpenHelp'=>'דו " ח זה מציג את כל פתח סיכונים אשר המשתמש הנוכחי בתור הבעלים או המנהל הקשורים עם הסיכון מסודרים לפי רמת הסיכון',
    'ReportOpenHelp'=>'דו " ח זה מציג את כל פתח סיכונים מסודרים לפי רמת הסיכון',
    'ReportProjectsHelp'=>'דו " ח זה מציג את כל לפתוח את הסיכונים בחשבון עבור פרוייקטים שהוזמנו על ידי רמת הסיכון',
    'ReportNextReviewHelp'=>'דו " ח זה מציג את כל פתח סיכונים מקובל עד הסקירה הבאה מסודרים לפי רמת הסיכון',
    'ReportProductionIssuesHelp'=>'דו " ח זה מציג את כל פתח סיכונים שהוגש כמו בעיות ייצור מסודרים לפי רמת הסיכון',
    'ReportRiskScoringHelp'=>'דו " ח זה מציג את כל הסיכון ניקוד שיטות סיכונים הבקיע באמצעות כל אחד',
    'ReportClosedHelp'=>'דו " ח זה מציג את כל סגור סיכונים מסודרים לפי רמת הסיכון',
    'ReportSubmittedByDateHelp'=>'דו " ח זה מציג את כל הסיכונים הורה עד מועד ההגשה',
    'ReportMitigationsByDateHelp'=>'דו " ח זה מציג את כל בהקלות מתוכנן הורה על ידי מיתון תאריך',
    'ReportMgmtReviewsByDateHelp'=>'דו " ח זה מציג את כל ניהול סקירות שהוזמנו על ידי סקירה תאריך',
    'ReportProjectsAndRisksHelp'=>'דו " ח זה מציג את כל הפרויקטים והסיכונים שהוקצו להם',
    'Language'=>'שפה',
    'AllOpenRisksNeedingReview'=>'כל פתח סיכונים הזקוקים סקירה',
    'ReportReviewNeededHelp'=>'דו " ח זה מציג את כל פתח הסיכונים צורך לסקירת הנהלה',
    'CustomValue'=>'ערך מותאם אישית',
    'ClosedRisksByDate'=>'סגור סיכונים לפי תאריך',
    'DateClosed'=>'תאריך סגור',
    'ClosedBy'=>'נסגר על ידי',
    'ReportClosedByDateHelp'=>'דו " ח זה מציג את כל הסיכונים הורה על ידי סגר תאריך',
    'AllOpenRisksByTeam'=>'כל פתח סיכונים על ידי צוות',
    'ReportRiskTeamsHelp'=>'דו " ח זה מציג את כל הקבוצות ואת הסיכונים שהוקצו לכל אחד',
    'Unassigned'=>'ב',
    'AllOpenRisksByTechnology'=>'כל פתח סיכונים על ידי טכנולוגיה.',
    'ReportRiskTechnologiesHelp'=>'דו " ח זה מציג את כל הטכנולוגיות ואת המוקצה לכל הסיכונים',
    'RiskLevel'=>'רמת הסיכון',
    'BasedOnTheCurrentRiskScore'=>'על סמך ציון הסיכון שלך, תאריך הסקירה הבא שלך יהיה ',
    'WouldYouLikeToUseADifferentDate'=>'האם אתה רוצה להשתמש תאריכים שונים במקום?',
    'RisksOpenedAndClosedOverTime'=>'הסיכונים נפתח ונסגר לאורך זמן',
    'AllRiskScoresAreAdjusted'=>'כל סיכון ניקוד מותאם כדי להתאים על 0-10 קנה מידה.',
    'DetermineProjectStatus'=>'לקבוע סטטוס הפרויקט',
    'ProjectStatusHelp'=>'המקום פרויקטים לתוך דליים מבוסס על הסטטוס הנוכחי שלהם.',
    'ActiveProjects'=>'פעיל פרויקטים',
    'OnHoldProjects'=>'בהמתנה פרויקטים',
    'CompletedProjects'=>'הושלמו פרויקטים',
    'CancelledProjects'=>'ביטלו פרויקטים',
    'UpdateProjectStatuses'=>'עדכון פרויקט סטטוסים',
    'HighRiskReport'=>'סיכון גבוה " ח',
    'TotalOpenRisks'=>'סה " כ פתח סיכונים',
    'TotalHighRisks'=>'הכולל סיכונים גבוהים',
    'HighRiskPercentage'=>'סיכון גבוה אחוז',
    'UpdateClassicScore'=>'עדכן את התוצאה קלאסי',
    'CurrentLikelihood'=>'הסבירות הנוכחי',
    'CurrentImpact'=>'השפעת הנוכחי',   
    'UpdateCVSSScore'=>'עדכון CVSS ציון',
    'BaseScoreMetrics'=>'בסיס ציון מדדים',
    'AttackVector'=>'ההתקפה',
    'AttackComplexity'=>'התקפה מורכבות',
    'Authentication'=>'אימות',
    'ConfidentialityImpact'=>'סודיות השפעה',
    'IntegrityImpact'=>'שלמות ההשפעה',
    'AvailabilityImpact'=>'זמינות השפעה',
    'TemporalScoreMetrics'=>'זמני ציון מדדים',
    'Exploitability'=>'לאפשרות של ניצול הפגיעות',
    'RemediationLevel'=>'תיקון רמת',
    'ReportConfidence'=>'דו " ח אמון',
    'EnvironmentalScoreMetrics'=>'סביבתי ציון מדדים',
    'CollateralDamagePotential'=>'בטחונות פוטנציאל הנזק',
    'TargetDistribution'=>'המטרה הפצה',
    'ConfidentialityRequirement'=>'סודיות דרישה',
    'IntegrityRequirement'=>'שלמות דרישה',
    'AvailabilityRequirement'=>'זמינות דרישה',
    'UpdateDREADScore'=>'עדכון חרדה ציון',
    'DamagePotential'=>'פוטנציאל הנזק',
    'Reproducibility'=>'שחזור',
    'AffectedUsers'=>'למשתמשים הרלוונטיים',
    'Discoverability'=>'גילוי',
    'UpdateOWASPScore'=>'עדכון OWASP ציון',
    'ThreatAgentFactors'=>'איום הסוכן גורמים',
    'SkillLevel'=>'רמת מיומנות',
    'Motive'=>'מניע',
    'Opportunity'=>'הזדמנות',
    'Size'=>'גודל',
    'VulnerabilityFactors'=>'פגיעות גורמים',
    'EaseOfDiscovery'=>'הקלות של גילוי',
    'EaseOfExploit'=>'קלות לנצל',
    'Awareness'=>'מודעות',
    'IntrusionDetection'=>'גילוי פריצה',
    'TechnicalImpact'=>'טכנית השפעה',
    'LossOfConfidentiality'=>'אובדן של סודיות.',
    'LossOfIntegrity'=>'אובדן של שלמות',
    'LossOfAvailability'=>'אובדן זמינות',
    'LossOfAccountability'=>'אובדן של אחריות',
    'BusinessImpact'=>'השפעות עסקיות',
    'FinancialDamage'=>'נזק כלכלי',
    'ReputationDamage'=>'המוניטין נזק',
    'NonCompliance'=>'אי-ציות',
    'PrivacyViolation'=>'הפרת פרטיות',
    'UpdateCustomScore'=>'עדכון ציון מותאם אישית',
    'ManuallyEnteredValue'=>'להזין באופן ידני את הערך',
    'ScoreByClassic'=>'ציון על ידי קלאסיקה',
    'ScoreByCVSS'=>'ציון על ידי CVSS',
    'ScoreByDREAD'=>'ציון על ידי חרדה',
    'ScoreByOWASP'=>'ציון על ידי OWASP',
    'ScoreByCustom'=>'ציון על ידי מותאם אישית',
    'BaseVector'=>'בסיס וקטור',
    'TemporalVector'=>'זמני וקטור',
    'EnvironmentalVector'=>'הסביבה וקטור',
    'SupportingDocumentation'=>'תיעוד התומך',
    'Import'=>'ייבוא',
    'Export'=>'יצוא',
    'ActivateTheImportExportExtra'=>'להפעיל את הייבוא/ייצוא נוסף.',
    'PrintableView'=>'להדפסה נוף',
    'GroupBy'=>'קבוצה על ידי',
    'IncludedColumns'=>'עמודות כלולות',
    'AllRisks'=>'כל הסיכונים',
    'DynamicRiskReport'=>'דינמי סיכון " ח',
    'ObsoleteReports'=>'מיושן דוחות',
    'Project'=>'הפרויקט',
    'SortBy'=>'מיין לפי',
    'MonthSubmitted'=>'בחודש שהוגשו',
    'AssetManagement'=>'ניהול נכסים',
    'AutomatedDiscovery'=>'גילוי אוטומטי',
    'BatchImport'=>'אצווה ליבוא',
    'ManageAssets'=>'ניהול נכסים',
    'AssetValuation'=>'הערכת שווי נכס',
    'AllowAccessToAssetManagementMenu'=>'לאפשר גישה ל "ניהול נכסים" תפריט',
    'AutomatedDiscoveryHelp'=>'לגלות את כל חי כתובות ה-IP של הזנת טווח IP. לחיות כתובות IP יתווסף נכס הרשימה. מקובל פורמטים:',
    'IPRange'=>'טווח IP',
    'Search'=>'חיפוש',
    'AddANewAsset'=>'הוספת נכס חדש',
    'DeleteAnExistingAsset'=>'מחיקת נכס קיים.',
    'AssetName'=>'שם הנכס',
    'IPAddress'=>'כתובת ה-IP',
    'AssetWasAddedSuccessfully'=>'נכס נוסף בהצלחה.',
    'AssetWasDeletedSuccessfully'=>'הנכס היה נמחק בהצלחה.',
    'ThereWasAProblemAddingTheAsset'=>'אין בעיה להוסיף את הנכס.',
    'ThereWasAProblemDeletingTheAsset'=>'יש בעיה למחוק את הנכס.',
    'ComingSoon'=>'בקרוב',
    'ExportRisks'=>'יצוא סיכונים',
    'ExportMitigations'=>'יצוא בהקלות',
    'ExportReviews'=>'יצוא ביקורות',
    'ExportCombined'=>'יצוא משולב',
    'MitigatedBy'=>'מיתנה על ידי',
    'MitigationId'=>'מיתון ID',
    'ReviewId'=>'ביקורת תעודת זהות',
    'AffectedAssets'=>'מושפעים הנכסים',
    'Activate'=>'הפעל',
    'DeleteRisks'=>'למחוק סיכונים',
    'DeletedRisksCannotBeRecovered'=>'נמחק סיכונים יכולים להיות התאושש',
    'RisksDeletedSuccessfully'=>'הסיכון(s) נמחק בהצלחה',
    'ThereWasAProblemDeletingTheRisk'=>'יש בעיה למחוק את הסיכון(s)',
    'Activated'=>'מופעל',
    'IWantToReviewInsignificantRiskEvery'=>'אני רוצה לבחון את משמעות הסיכון בכל',
    'Insignificant'=>'חשיבות',
    'IConsiderVeryHighRiskToBeAnythingGreaterThan'=>'אני מחשיב מאוד סיכון גבוה להיות משהו גדול יותר',
    'IConsiderHighRiskToBeLessThanAboveButGreaterThan'=>'אני מחשיב את סיכון גבוה להיות פחות לעיל, אבל גדול יותר',
    'VeryHigh'=>'גבוהה מאוד',
    'VeryHighRisk'=>'סיכון גבוה מאוד',
    'IWantToReviewVeryHighRiskEvery'=> 'אני רוצה לבחון את סיכון גבוה מאוד בכל',
    'AbleToReviewVeryHighRisks'=>'מסוגל לבחון סיכונים גבוהים',
    'AbleToReviewInsignificantRisks'=>'תוכל לסקור את חשיבות סיכונים',
    'TotalVeryHighRisks'=>'הכולל סיכונים גבוהים',
    'VeryHighRiskPercentage'=>'סיכון גבוה מאוד אחוז',
    'AllTeams'=>'כל הצוותים.',
    'FileUploadSettings'=>'העלאת קובץ הגדרות',
    'AllowedFileTypes'=>'מותר סוגי קבצים',
    'AddNewFileTypeOf'=>'להוסיף את סוג הקובץ החדש של',
    'DeleteCurrentFileTypeOf'=>'מחק את הקובץ הנוכחי סוג של',
    'MaximumUploadFileSize'=>'גודל קובץ מקסימלי להעלאה',
    'Bytes'=>'בתים',
    'CheckAll'=>'לבדוק את כל',
    'CheckAllRiskMgmt'=>'לבדוק את כל ניהול סיכונים',
    'CheckAllAssetMgmt'=>'לבדוק את כל ניהול נכסים',
    'CheckAllConfigure'=>'לבדוק את כל להגדיר',
    'MitigationTeam'=>'מיתון צוות',
    'ImportRisks'=>'ייבוא סיכונים',
    'ImportAssets'=>'ייבוא נכסים',
    'AssetValue'=>'ערך הנכס',
    'Register'=>'הרשמה',
    'RegisterSimpleRisk'=>'הרשמה SimpleRisk',
    'RegistrationText'=>'על ידי רישום SimpleRisk תוכל לספק את פרטי הקשר שלך, כך שאתה יכול להיות מעודכן עם המהדורה העדכנית ביותר ומידע חשוב של הודעות אבטחה. המידע שלך לעולם לא מכר צד שלישי. נרשמו מקרים גם יש את היכולת לגבות, לשדרג בלחיצת כפתור.',
    'RegistrationInformation'=>'רישום מידע',
    'Company'=>'החברה',
    'JobTitle'=>'כותרת העבודה',
    'Phone'=>'טלפון',
    'UpgradeSimpleRisk'=>'שדרוג SimpleRisk',
    'UpgradeInstructions'=>'סעיף זה משתמש שדרוג נוסף. כדי לוודא שיש לך את הגרסה העדכנית ביותר, בחר "עדכון", לרשום מחדש, לטעון מחדש את הדף.',
    'NoUpgradeNeeded'=>'לא נדרש שדרוג בשלב זה.',
    'BackupDatabase'=>'גיבוי מסד הנתונים',
    'UpgradeApplication'=>'לשדרג את היישום',
    'UpgradeDatabase'=>'לשדרג את מסד הנתונים',
    'CustomExtras'=>'תוספות מותאמות אישית',
    'CustomExtrasText'=>'זה יהיה מדהים אם הכל בחינם, נכון? בתקווה הליבה SimpleRisk פלטפורמה הוא מסוגל לשרת את כל ניהול סיכונים צריך. אבל, אם אתה מוצא את עצמך עדיין רוצה עוד פונקציונליות, פיתחנו סדרה של "תוספות" זה יעשה בדיוק את זה.',
    'Upgrade'=>'שדרוג',
    'Install'=>'להתקין',
    'Purchase'=>'רכישה',
    'PasswordPolicy'=>'הסיסמה מדיניות',
    'MinimumNumberOfCharacters'=>'מספר מינימאלי של תווים',
    'RequireAlphaCharacter'=>'דורשים אלפא הדמות',
    'RequireUpperCaseCharacter'=>'דורשים רישיות תווים',
    'RequireLowerCaseCharacter'=>'לדרוש באותיות הדמות',
    'RequireNumericCharacter'=>'דורשים מספרי הדמות',
    'RequireSpecialCharacter'=>'דורשים תווים מיוחדים',
    'Enabled'=>'זמין',
    'RiskPyramid'=>'הסיכון פירמידה',
    'RiskPyramidDescription'=>'הסיכון פירמידה מעל עוזר להראות את חלוקת הסיכונים בין רמות סיכון שונות. חלק עליון כבד הפירמידה יכול להיות סימן כי הארגון לוקח סיכון גדול מדי.',
    'RiskAdvice'=>'הסיכון עצה',
    'AddDeleteAssets'=>'הוסף & מחק נכסים',
    'EditAssets'=>'עריכה נכסים',
    'AutomaticAssetValuation'=>'אוטומטי הערכת שווי נכס',
    'ManualAssetValuation'=>'מדריך הערכת שווי נכס',
    'MinimumValue'=>'הערך המינימלי',
    'MaximumValue'=>'הערך המרבי',
    'ValueRange'=>'טווח הערכים',
    'DefaultAssetValuation'=>'ברירת מחדל של הערכת שווי נכס',
    'Default'=>'ברירת המחדל',
    'RisksAndAssets'=>'סיכונים ונכסים',
    'Report'=>'דו " ח',
    'RisksByAsset'=>'סיכונים על ידי נכס',
    'AssetsByRisk'=>'נכסים ע " י סיכון',
    'MaximumQuantitativeLoss'=>'מרבית כמותית אובדן',
    'MitigationOwner'=>'מיתון הבעלים',
    'MitigationCost'=>'הפחתת עלות',
    'RiskColumns'=>'הסיכון עמודות',
    'MitigationColumns'=>'מיתון עמודות',
    'ReviewColumns'=>'סקירה עמודות',
    'ChangeStatus'=>'שינוי סטטוס',
    'SetRiskStatusTo'=>'הגדרת מצב סיכון',
    'AddNewStatusNamed'=>'הוספת מצב חדש בשם',
    'DeleteStatusNamed'=>'למחוק את הסטטוס בשם',
    'DefaultCurrencySymbol'=>'ברירת מחדל של סמל המטבע',
    'DefaultValues'=>'ערכי ברירת מחדל',
    'RiskSource'=>'הסיכון המקור',
    'AddNewSourceNamed'=>'להוסיף מקור חדש בשם',
    'DeleteSourceNamed'=>'למחוק את המקור בשם',
    'CheckAllAssessments'=>'לבדוק את כל הערכות',
    'AllowAccessToAssessmentsMenu'=>'לאפשר גישה ל "הערכות" תפריט',
    'Assessments'=>'הערכות',
    'AvailableAssessments'=>'זמין הערכות',
    'PendingRisks'=>'ממתינים סיכונים',
    'CreateAssessment'=>'ליצור הערכה',
    'EditAssessment'=>'ערוך הערכה',
    'Overview'=>'סקירה',
    'OpenVsClosed'=>'פתוח לעומת סגור',
    'MitigatedVsUnmitigated'=>'למתן לעומת מוחלט',
    'ReviewedVsUnreviewed'=>'בביקורת לעומת Unreviewed',
    'OpenedRisks'=>'פתח סיכונים',
    'MailSettings'=>'הגדרות דואר',
    'TransportAgent'=>'תחבורה הסוכן',
    'FromName'=>'שם',
    'FromEmail'=>'מתוך דוא " ל',
    'ReplyToName'=>'ReplyTo שם',
    'ReplyToEmail'=>'ReplyTo דוא " ל',
    'Host'=>'המארח',
    'SMTPAuthentication'=>'אימות SMTP',
    'Encryption'=>'הצפנה',
    'Port'=>'נמל',
    'Next'=>'הבא',
    'NewAssessmentQuestion'=>'חדש הערכת שאלה',
    'Question'=>'שאלה',
    'RiskScore'=>'הסיכון ציון',
    'SubmitRisk'=>'להגיש סיכון',
    'Answer'=>'תשובה',
    'AddQuestion'=>'הוספת שאלה',
    'SaveAssessment'=>'להציל את הערכה',
    'SendAssessment'=>'שלח הערכה',
    'DeleteAssessment'=>'למחוק את הערכה',
    'AssessmentName'=>'הערכה השם',
    'SendTo'=>'לשלוח',
    'ActiveAssessments'=>'פעיל הערכות',
    'SentTo'=>'שלח',
    'From'=>'מ',
    'Key'=>'מפתח',
    'GoToSSOLoginPage'=>'לעבור לדף ההתחברות SSO',
    'APIKey'=>'API מפתח',
    'GenerateAPIKey'=>'יצירת API מפתח',
    'RotateAPIKey'=>'סיבוב המפתח של',
    'InvalidateAPIKey'=>'לפסול API מפתח',
    'Deactivate'=>'לבטל',
    'ImportExportExtra'=>'ייבוא-ייצוא נוסף',
    'SaveDetails'=>'לשמור פרטי',
    'ClearForm'=>'נקה טופס',
    'SaveMitigation'=>'שמור להפחתת הסיכון',
    'Cancel'=>'ביטול',
    'SubmitReview'=>'שלח סקירה',
    'UnassignedRisks'=>'סיכונים שלא הוקצו',
    'DisableRegistrationNotice'=>'בטל את רישום הודעה',
    'UserPolicy'=>'מדיניות משתמש',
    'UseCaseSensitiveValidationOfUsername'=>'השתמש תלויי רישיות אימות של שם המשתמש',
    'MitigationPlanning'=>'התאריך המתוכנן להפחתת הסיכון',
    'AssetDetails'=>'פרטי נכס',
    'RiskList'=>'רשימת סיכונים',
    'Are you sure you want to close the risk? All changes will be lost!'=>'האם אתה בטוח שברצונך לסגור את הסיכון? כל השינויים יאבדו!',
    'MinimumPasswordAge' => 'גיל הסיסמה המינימלי',
    'MaximumPasswordAge' => 'גיל סיסמה מרבי',
    'MaximumAttemptsLockout' => 'נעילת ניסיונות מרבי',
    'MaximumAttemptsLockoutTime' => 'ניסיונות מרבי נעילת זמן',
    'attempts' => 'ניסיונות',
    'minutes' => 'דקות',
    'AccountLockedOut' => 'חשבון נעול',
    'AccountLockoutPolicy' => 'מדיניות נעילת חשבון',
    'ImportExportIsDeactivated' => 'ייבוא-ייצוא מבוטלת',
    'PurchaseTheExtra' => 'הרכישה הנוספת',
    'ExpandAll' => 'הרחבת כל',
    'ConditionMessageForMinChar' => 'הסיסמה צריכה להכיל המינימום של הדמויות $min_chars.',
    'ConditionMessageForAlpha' => 'הסיסמה צריכה להכיל תו אלפא.',
    'ConditionMessageForUppercase' => 'הסיסמה צריכה להכיל תו אותיות רישיות.',
    'ConditionMessageForLowercase' => 'הסיסמה צריכה להכיל תו של אות קטנה.',
    'ConditionMessageForDigit' => 'הסיסמה צריכה להכיל ספרה.',
    'ConditionMessageForSpecialchar' => 'הסיסמה צריכה להכיל תו מיוחד.',
    'ConditionMessageForMinPasswordAge' => 'הסיסמה ניתן לעדכן מ עודכן זמן המינימום של $min_password_age ימים מאוחר יותר.',
    'TrustedDomains' => 'קבוצות מחשבים מהימנות',
    'SimpleRiskColumnMapping' => 'מיפוי העמודה SimpleRisk',
    'Mapping' => 'מיפוי',
    'Optional' => 'אופציונלי',
    'SaveMappingAs' => 'להציל את מיפוי כמו',
    'EncryptionLevel' => 'רמת ההצפנה',
    'Level' => 'רמת',
    'Description' => 'תיאור',
    'ShowRiskScoreOverTime' => 'הצג ציון הסיכון לאורך זמן',
    'HideRiskScoreOverTime' => 'הסתר ציון הסיכון לאורך זמן',
    'ReviewRegularlyHelp' => 'לפניכם הרשימה של כל הסיכונים ממוין לפי Unreviewed, בעבר יעד ותאריך הבא סקירה',
    'RiskScoringHistory' => 'הסיכון הבקיע היסטוריה',
    'RiskAddPermissionMessage' => 'אין לך הרשאה לשלוח סיכונים חדשים.  סיכונים כי תנסה לשלוח לא תוקלט.  נא פנה אל מנהל מערכת אם תרגישי שהגעת הודעה זו בטעות.',
    'SubjectRiskCannotBeEmpty' => 'הנושא של סיכון אינו יכול להיות ריק',
    'InvalidRiskID' => 'מזהה סיכון לא חוקי',
    'Success' => 'הצלחה',
    'RiskUpdatePermissionMessage' => 'אין לך הרשאה לשינוי סיכונים.  סיכונים כי תנסה לשנות לא תוקלט.  נא פנה אל מנהל מערכת אם תרגישי שהגעת הודעה זו בטעות.',
    'RiskReviewPermission' => 'אין לך הרשאה כדי לסקור את הסיכונים ברמה $risk_level.  חוות דעת של כל ניסיון לשלוח לא תוקלט.  נא פנה אל מנהל מערכת אם תרגישי שהגעת הודעה זו בטעות.',
    'DateAndTime' => 'תאריך ושעה',
    'mCryptWarning' => 'הסיומת "mcrypt" צריך להיות מותקן עבור תוספת הצפנה לפעול כהלכה.  בבקשה להתקין אותו כדי להמשיך.',
    'APIInCompatibleWithEncryptionLevel' => 'ה-API אינה תואמת רמת ההצפנה משתמש נוסף מסד נתונים מוצפנים.',
    'UnauthenticatedAccessInAPI' => 'גישה לא מאומתת.  היכנס למערכת או לספק מפתח כדי להשתמש ב- API של SimpleRisk.',
    'ResetPasswordMessageInUserLevelEncryption' => 'לא ניתן לאפס את הסיסמה כי המשתמש זו ברמת משתמש בהצפנת. נא פנה למנהל המערכת.',
    'YouNeedToSpecifyAnIdParameter' => 'עליך לציין פרמטר מזהה.',
    'NoMitigation' => 'יש אין להפחתת הסיכון שצוינה.',
    'NoReview' => 'יש אין סקירה שצוין.',
    'MysqldumpPathWasSavedSuccessfully'=>'נתיב Mysqldump נשמרה בהצלחה.',
    'UnavailableMysqldumpService'=>'שאין שירות \'mysqldump\' והמשאב בשרת. נא הגדר נתיב השירות mysqldump מוחלטת.',
    'AllOpenRisksByTeamByLevel'=>'סיכונים הפתוחים על ידי צוות לפי רמת הסיכון',
    '' => '',
);

?>
