/**
 * Nucleus HMS — Internationalization (i18n) Engine
 * Supports seamless instant switching between English (en) and Amharic (am)
 */

const translations = {
  en: {
    // Header & Brand
    brand_name: "Nucleus HMS",
    system_title: "Nucleus Hospital Management System",
    welcome: "Welcome",
    search_placeholder: "Search patients, doctors...",
    cmd_placeholder: "Search patients, doctors, rooms, or actions...",
    language: "Language",
    english: "English",
    amharic: "አማርኛ",
    
    // Sidebar Sections
    sb_sec_overview: "Overview",
    sb_sec_clinical: "Clinical",
    sb_sec_facility: "Facility",
    sb_sec_finance: "Finance",
    sb_sec_system: "System",
    
    // Nav Items
    nav_dashboard: "Dashboard",
    nav_patients: "Patients",
    nav_doctors: "Doctors",
    nav_appointments: "Appointments",
    nav_treatments: "Treatments",
    nav_rooms: "Rooms",
    nav_admissions: "Admissions",
    nav_billing: "Billing",
    nav_medications: "Medications",
    nav_departments: "Departments",
    nav_users: "Users",
    nav_settings: "Settings",
    nav_logout: "Logout",
    
    // Stats Cards
    stat_total_patients: "Total Patients",
    stat_active_doctors: "Active Doctors",
    stat_scheduled_appts: "Appointments",
    stat_rooms_available: "Available Rooms",
    stat_active_admissions: "Active Admissions",
    stat_unpaid_invoices: "Unpaid Invoices",
    stat_total_revenue: "Total Revenue (ETB)",
    
    // AI Assistant
    ai_launcher_text: "Nucleus AI Assistant",
    ai_title: "Nucleus AI Assistant",
    ai_status: "Active · Clinical Reminders",
    ai_quick_appts: "📅 Upcoming Appointments",
    ai_quick_reminders: "📱 Draft Reminders",
    ai_quick_status: "🏥 Hospital Status",
    ai_placeholder: "Ask AI Assistant or type patient name...",
    ai_send_sms: "📱 Send SMS",
    ai_send_whatsapp: "💬 Send WhatsApp",
    ai_reminder_draft: "AI Reminder Draft",
    ai_thinking: "Thinking...",
    ai_rescan: "Rescan Appointments",
    
    // Buttons & Common
    btn_add_patient: "+ Add Patient",
    btn_add_doctor: "+ Add Doctor",
    btn_add_appt: "+ Schedule Appointment",
    btn_add_room: "+ Add Room",
    btn_add_admission: "+ New Admission",
    btn_add_invoice: "+ Create Invoice",
    btn_add_user: "+ Create User",
    btn_save: "Save",
    btn_cancel: "Cancel",
    btn_delete: "Delete",
    btn_edit: "Edit",
    btn_close: "Close",
    
    // Table Headers
    th_id: "#",
    th_name: "Name",
    th_gender: "Gender",
    th_dob: "Date of Birth",
    th_phone: "Phone",
    th_email: "Email",
    th_address: "Address",
    th_actions: "Actions",
    th_specialization: "Specialization",
    th_department: "Department",
    th_date: "Date & Time",
    th_status: "Status",
    th_room: "Room Number",
    th_type: "Type",
    th_amount: "Amount (ETB)",
    
    // Settings
    sett_title: "System Settings",
    sett_general: "General",
    sett_appearance: "Appearance",
    sett_account: "Account",
    sett_notifications: "Alerts",
    
    // Toast Messages
    toast_login_success: "Successfully logged in to Nucleus HMS",
    toast_settings_saved: "Settings saved successfully",
    toast_notif_sent: "Notification sent successfully",
    toast_record_saved: "Record saved successfully"
  },
  
  am: {
    // Header & Brand
    brand_name: "ኒውክለየስ 病院",
    system_title: "ኒውክለየስ ሆስፒታል ማኔጅመንት ሲስተም",
    welcome: "እንኳን ደህና መጡ",
    search_placeholder: "ታካሚዎችን፣ ዶክተሮችን ይፈልጉ...",
    cmd_placeholder: "ታካሚዎችን፣ ዶክተሮችን፣ ክፍሎችን ይፈልጉ...",
    language: "ቋንቋ",
    english: "English",
    amharic: "አማርኛ",
    
    // Sidebar Sections
    sb_sec_overview: "አጠቃላይ",
    sb_sec_clinical: "ህክምና",
    sb_sec_facility: "ህንፃና ክፍሎች",
    sb_sec_finance: "ፋይናንስ",
    sb_sec_system: "ሲስተም",
    
    // Nav Items
    nav_dashboard: "ዳሽቦርድ",
    nav_patients: "ታካሚዎች",
    nav_doctors: "ዶክተሮች",
    nav_appointments: "ቀጠሮዎች",
    nav_treatments: "ህክምናዎች",
    nav_rooms: "ክፍሎች",
    nav_admissions: "ክፍል የገቡ",
    nav_billing: "ክፍያና ደረሰኝ",
    nav_medications: "መድኃኒቶች",
    nav_departments: "ዲፓርትመንቶች",
    nav_users: "ተጠቃሚዎች",
    nav_settings: "ማስተካከያዎች",
    nav_logout: "ውጣ",
    
    // Stats Cards
    stat_total_patients: "ጠቅላላ ታካሚዎች",
    stat_active_doctors: "በስራ ላይ ያሉ ዶክተሮች",
    stat_scheduled_appts: "የተያዙ ቀጠሮዎች",
    stat_rooms_available: "ነጻ ክፍሎች",
    stat_active_admissions: "አልጋ ላይ ያሉ ታካሚዎች",
    stat_unpaid_invoices: "ያልተከፈሉ ደረሰኞች",
    stat_total_revenue: "ጠቅላላ ገቢ (ብር)",
    
    // AI Assistant
    ai_launcher_text: "ኒውክለየስ ኤአይ ረዳት",
    ai_title: "ኒውክለየስ ኤአይ ረዳት",
    ai_status: "ንቁ · ክሊኒካዊ ማስታወሻዎች",
    ai_quick_appts: "📅 የሚመጡ ቀጠሮዎች",
    ai_quick_reminders: "📱 ረቂቅ ማስታወሻዎች",
    ai_quick_status: "🏥 የሆስፒታል ሁኔታ",
    ai_placeholder: "ኤአይ ረዳቱን ጠይቅ ወይም የታካሚ ስም ፃፍ...",
    ai_send_sms: "📱 በSMS ላክ",
    ai_send_whatsapp: "💬 በWhatsApp ላክ",
    ai_reminder_draft: "የኤአይ ማስታወሻ ረቂቅ",
    ai_thinking: "በማሰብ ላይ...",
    ai_rescan: "ቀጠሮዎችን እንደገና ቃኝ",
    
    // Buttons & Common
    btn_add_patient: "+ ታካሚ መዝግብ",
    btn_add_doctor: "+ ዶክተር መዝግብ",
    btn_add_appt: "+ ቀጠሮ ያዙ",
    btn_add_room: "+ ክፍል ጨምር",
    btn_add_admission: "+ ክፍል አግባ",
    btn_add_invoice: "+ ደረሰኝ ቁረጥ",
    btn_add_user: "+ ተጠቃሚ ፍጠር",
    btn_save: "አስቀምጥ",
    btn_cancel: "ሰርዝ",
    btn_delete: "አጥፋ",
    btn_edit: "አስተካክል",
    btn_close: "ዝጋ",
    
    // Table Headers
    th_id: "መታወቂያ",
    th_name: "ስም",
    th_gender: "ጾታ",
    th_dob: "የትውልድ ቀን",
    th_phone: "ስልክ",
    th_email: "ኢሜይል",
    th_address: "አድራሻ",
    th_actions: "ድርጊቶች",
    th_specialization: "ሙያ",
    th_department: "ክፍል",
    th_date: "ቀን እና ሰዓት",
    th_status: "ሁኔታ",
    th_room: "የክፍል ቁጥር",
    th_type: "ዓይነት",
    th_amount: "መጠን (ብር)",
    
    // Settings
    sett_title: "የሲስተም ማስተካከያዎች",
    sett_general: "ጠቅላላ",
    sett_appearance: "ገጽታ",
    sett_account: "መለያ",
    sett_notifications: "ማስታወቂያዎች",
    
    // Toast Messages
    toast_login_success: "ወደ ኒውክለየስ ሲስተም በተካካዮች ገብተዋል",
    toast_settings_saved: "ማስተካከያዎች በተሳካ ሁኔታ ተቀምጠዋል",
    toast_notif_sent: "ማስታወቂያው በተሳካ ሁኔታ ተልኳል",
    toast_record_saved: "መረጃው በተሳካ ሁኔታ ተቀምጧል"
  }
};

let currentLang = localStorage.getItem('nucleus_lang') || 'en';

function setLanguage(lang) {
  if (!translations[lang]) return;
  currentLang = lang;
  localStorage.setItem('nucleus_lang', lang);
  applyTranslations();
  
  // Trigger event for dynamic JS components
  document.dispatchEvent(new CustomEvent('languageChanged', { detail: { lang } }));
}

function t(key) {
  return (translations[currentLang] && translations[currentLang][key]) || translations['en'][key] || key;
}

function applyTranslations() {
  document.querySelectorAll('[data-i18n]').forEach(el => {
    const key = el.getAttribute('data-i18n');
    if (!key) return;
    const translatedText = t(key);
    
    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
      el.placeholder = translatedText;
    } else {
      // Find direct text node to preserve child icons and badge elements
      let textNode = Array.from(el.childNodes).find(n => n.nodeType === Node.TEXT_NODE && n.nodeValue.trim() !== '');
      if (textNode) {
        textNode.nodeValue = ' ' + translatedText + ' ';
      } else if (!el.children.length) {
        el.textContent = translatedText;
      }
    }
  });

  // Sync header language selector dropdown
  const select = document.getElementById('lang-select');
  if (select) select.value = currentLang;

  // Update HTML lang attribute
  document.documentElement.lang = currentLang;
}

document.addEventListener('DOMContentLoaded', () => {
  applyTranslations();
});

// ════ AMHARIC TO ENGLISH NAME MAPPING & SEARCH NORMALIZER ════
const AMHARIC_NAME_MAP = {
  // Common Ethiopian First Names
  'አልማዝ': 'Almaz',
  'አበበ': 'Abebe',
  'ጫላ': 'Chala',
  'ትግስት': 'Tigist',
  'ሰለሞን': 'Solomon',
  'ሕይወት': 'Hiwot',
  'ዳዊት': 'Dawit',
  'ቤተልሔም': 'Bethlehem',
  'ዮናስ': 'Yonas',
  'ኃይለማርያም': 'Hailemariam',
  'መሐመድ': 'Mohammed',
  'ፋጡማ': 'Fatuma',
  'ሀይሉ': 'Hailu',
  'ግርማ': 'Girma',
  'ታደለ': 'Tadele',
  'መላኩ': 'Melaku',
  'አስናቀ': 'Asnake',
  'ወንድሙ': 'Wondimu',
  'ኤልያስ': 'Elias',
  'ሀና': 'Hanna',
  'መብራቱ': 'Mebratu',

  // Common Ethiopian Last Names / Surnames
  'ተስፋዬ': 'Tesfaye',
  'ከበደ': 'Kebede',
  'በቀለ': 'Bekele',
  'አሰፋ': 'Assefa',
  'ብርሃኑ': 'Berhanu',
  'ታደሰ': 'Tadesse',
  'አለሙ': 'Alemu',
  'ወርቁ': 'Worku',
  'ተክሌ': 'Tekle',
  'ሀይሌ': 'Haile',
  'ገብሬ': 'Gebre',
  'ነጋሽ': 'Negash',
  'ደስታ': 'Desta',
  'በላይ': 'Belay',
  'ተሾመ': 'Teshome',
  'እስቲፋኖስ': 'Estifanos'
};

/**
 * Case-insensitive search normalizer with automatic Amharic-to-English name mapping.
 * Translates any Ge'ez script words (e.g. 'አልማዝ ተስፋዬ') into English ('almaz tesfaye')
 * and normalizes whitespace/case so searches like 'where is አልማዝ ተስፋዬ' match 'Almaz Tesfaye'.
 */
function normalizeSearchQuery(query) {
  if (!query) return '';
  let q = query.toLowerCase().trim();

  // Replace all mapped Amharic terms with English counterparts
  for (const [amharic, english] of Object.entries(AMHARIC_NAME_MAP)) {
    if (q.includes(amharic)) {
      q = q.replace(new RegExp(amharic, 'g'), english.toLowerCase());
    }
  }

  return q;
}
