(() => {
  const dictionaries = {
    fr: {
      nav_home: "Accueil",
      nav_plans: "Forfaits",
      nav_orders: "Mes commandes",
      hero_choose: "Choisissez votre",
      operator_word: "opérateur",
      plans_orange: "Forfaits Orange",
      plans_mtn: "Forfaits MTN",
      plans_camtel: "Forfaits Camtel",
      choose_operator: "Choisissez votre opérateur",
      intro_copy: "Des forfaits Orange, MTN et Camtel au Cameroun, au prix affiché.",
      mobile_internet: "INTERNET MOBILE, SIMPLEMENT",
      subtitle: "Trouvez le forfait internet qui vous convient.",
      see_plans: "Voir les forfaits",
      secure_payment: "Paiement sécurisé",
      fast_delivery: "Livraison rapide",
      no_account: "Sans compte client",
      how_title: "Comment ça marche",
      step_one: "Choisissez votre forfait",
      step_two: "Payez avec Mobile Money",
      step_three: "Recevez votre forfait",
      popular_offers: "Forfaits populaires",
      see_everything: "Tout voir",
      clear_prices: "Choisissez un forfait et payez avec Mobile Money.",
      process_subtitle: "Votre forfait, en trois étapes faciles.",
      reviews_title: "Ils nous font confiance",
      reviews_subtitle: "Les avis de nos clients.",
      how_to_return: "Retour à l’accueil",
      plans_heading: "Tous les forfaits",
      plans_subtitle: "Choisissez votre opérateur pour voir ses offres.",
      plans_description: "Choisissez le forfait qui vous convient.",
      back_plans: "Retour aux forfaits",
      checkout_title: "Finaliser votre achat",
      checkout_description: "Vos informations servent à créer le paiement et à retrouver votre commande.",
      full_name: "Nom complet",
      delivery_number: "Numéro qui va recevoir le forfait",
      payment_phone: "Numéro qui va effectuer le paiement",
      payment_phone_help: "Numéro Orange Money ou MTN Mobile Money qui sera débité.",
      email_address: "Adresse e-mail",
      payment_method: "Moyen de paiement",
      continue_payment: "Continuer vers le paiement",
      your_bundle: "Votre forfait",
      operator_label: "Opérateur",
      bundle_label: "Forfait",
      internet_label: "Internet",
      validity_label: "Validité",
      total_label: "Total",
      secure_payment_note: "Vos données de paiement restent chez Bumxpress. Forfait Pulse ne demande jamais votre code PIN Mobile Money.",
      orders_subtitle: "Suivez le paiement et la livraison de vos forfaits.",
      buy: "Acheter",
      valid_for: "Valable",
      days: "jours",
      help: "Aide",
      filter_all: "Tous",
      filter_daily: "Journaliers",
      filter_weekly: "Hebdomadaires",
      filter_monthly: "Mensuels",
      no_orders: "Aucune commande pour le moment.",
      no_orders_help: "Choisissez un forfait pour commencer."
    },
    en: {
      nav_home: "Home",
      nav_plans: "Bundles",
      nav_orders: "My orders",
      hero_choose: "Choose your",
      operator_word: "operator",
      plans_orange: "Orange bundles",
      plans_mtn: "MTN bundles",
      plans_camtel: "Camtel bundles",
      choose_operator: "Choose your operator",
      intro_copy: "Orange, MTN and Camtel bundles in Cameroon, at the price shown.",
      mobile_internet: "MOBILE INTERNET, MADE SIMPLE",
      subtitle: "Find the data bundle that works for you.",
      see_plans: "View bundles",
      secure_payment: "Secure payment",
      fast_delivery: "Quick delivery",
      no_account: "No customer account",
      how_title: "How it works",
      step_one: "Choose your bundle",
      step_two: "Pay with Mobile Money",
      step_three: "Receive your bundle",
      popular_offers: "Popular bundles",
      see_everything: "View all",
      clear_prices: "Choose a bundle and pay with Mobile Money.",
      process_subtitle: "Your bundle in three easy steps.",
      reviews_title: "Trusted by our customers",
      reviews_subtitle: "Customer reviews.",
      how_to_return: "Back home",
      plans_heading: "All bundles",
      plans_subtitle: "Choose your operator to see its offers.",
      plans_description: "Choose the bundle that works for you.",
      back_plans: "Back to bundles",
      checkout_title: "Complete your purchase",
      checkout_description: "Your information is used to create the payment and find your order.",
      full_name: "Full name",
      delivery_number: "Phone number that will receive the bundle",
      payment_phone: "Phone number that will make the payment",
      payment_phone_help: "Orange Money or MTN Mobile Money number to be charged.",
      email_address: "Email address",
      payment_method: "Payment method",
      continue_payment: "Continue to payment",
      your_bundle: "Your bundle",
      operator_label: "Operator",
      bundle_label: "Bundle",
      internet_label: "Data",
      validity_label: "Validity",
      total_label: "Total",
      secure_payment_note: "Your payment details stay with Bumxpress. Forfait Pulse never asks for your Mobile Money PIN.",
      orders_subtitle: "Track payment and delivery for your bundles.",
      buy: "Buy",
      valid_for: "Valid for",
      days: "days",
      help: "Help",
      filter_all: "All",
      filter_daily: "Daily",
      filter_weekly: "Weekly",
      filter_monthly: "Monthly",
      no_orders: "No orders yet.",
      no_orders_help: "Choose a bundle to get started."
    }
  };

  let lang = document.documentElement.lang === "en" ? "en" : "fr";
  const applyLanguage = (next) => {
    lang = next;
    document.documentElement.lang = next;
    document.querySelectorAll("[data-i18n]").forEach((element) => {
      const value = dictionaries[next][element.dataset.i18n];
      if (value) element.textContent = value;
    });
  };

  document.querySelectorAll("[data-lang-toggle]").forEach((button) => {
    button.addEventListener("click", () => applyLanguage(lang === "fr" ? "en" : "fr"));
  });

  const operatorPicker = document.querySelector("[data-operator-picker]");
  if (operatorPicker) {
    operatorPicker.addEventListener("click", (event) => {
      const button = event.target.closest("[data-operator]");
      if (!button) return;
      operatorPicker.querySelectorAll("[data-operator]").forEach((item) => item.classList.remove("selected"));
      button.classList.add("selected");
      const target = button.dataset.operator;
      window.location.href = `/forfaits.php?operateur=${encodeURIComponent(target)}`;
    });
  }

  const categoryFilters = document.querySelector("[data-category-filters]");
  if (categoryFilters) {
    categoryFilters.addEventListener("click", (event) => {
      const button = event.target.closest("[data-filter]");
      if (!button) return;
      const filter = button.dataset.filter;
      categoryFilters.querySelectorAll("[data-filter]").forEach((item) => item.classList.toggle("selected", item === button));
      document.querySelectorAll("[data-days]").forEach((card) => {
        const days = Number(card.dataset.days);
        const visible = filter === "all"
          || (filter === "daily" && days <= 1)
          || (filter === "weekly" && days > 1 && days <= 7)
          || (filter === "monthly" && days > 7);
        card.hidden = !visible;
      });
    });
  }

  const stepThree = document.querySelector("[data-admin-secret-step]");
  if (stepThree) {
    let clicks = [];
    stepThree.addEventListener("click", () => {
      const now = Date.now();
      clicks = clicks.filter((time) => now - time < 1600);
      clicks.push(now);
      if (clicks.length >= 5) {
        window.location.href = "/admin/login.php";
      }
    });
    stepThree.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        stepThree.click();
      }
    });
  }

  document.querySelectorAll("[data-confirm]").forEach((form) => {
    form.addEventListener("submit", (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });

  applyLanguage(lang);
})();
