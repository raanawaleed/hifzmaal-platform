<script setup>
const lastUpdated = 'July 8, 2026'
</script>

<template>
  <div class="min-h-screen bg-white text-gray-900 dark:bg-slate-900 dark:text-slate-100">
    <header class="mx-auto flex max-w-3xl items-center gap-2 px-6 py-6">
      <router-link to="/" class="flex items-center gap-2">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-sm font-black text-white">
          HM
        </div>
        <span class="text-lg font-bold">HifzMaal</span>
      </router-link>
    </header>

    <main class="mx-auto max-w-3xl px-6 pb-24 text-sm leading-relaxed text-gray-700 dark:text-slate-300">
      <h1 class="mb-2 text-3xl font-black text-gray-900 dark:text-slate-100">Privacy Policy</h1>
      <p class="mb-8 text-gray-400 dark:text-slate-500">Last updated: {{ lastUpdated }}</p>

      <p class="mb-6">
        This Privacy Policy explains what information HifzMaal collects, how we use it, and the
        choices you have.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">1. Information we collect</h2>
      <ul class="mb-4 list-disc space-y-1 pl-5">
        <li><strong>Account data:</strong> name, email address, password (stored hashed), locale, and timezone.</li>
        <li><strong>Financial data you enter:</strong> accounts, transactions, bills, budgets, savings goals, and Zakat calculations for your family workspace.</li>
        <li><strong>Billing data:</strong> your subscription plan and status. Card details are handled directly by Stripe — we never see or store your full card number.</li>
        <li><strong>Usage data:</strong> basic technical logs (IP address, browser) used for security and troubleshooting.</li>
      </ul>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">2. How we use your information</h2>
      <p class="mb-4">
        We use your information to operate the Service: to authenticate you, run the finance and
        Zakat features you use, send transactional emails (bill reminders, invitations, password
        resets, receipts), process subscription payments through Stripe, and improve reliability
        and security.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">3. Sharing</h2>
      <p class="mb-4">
        We do not sell your personal data. We share data only with service providers who help us
        run HifzMaal — for example Stripe (payments) and our email delivery provider — under
        agreements that restrict them to using it solely to provide that service, or when required
        by law.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">4. Family workspaces</h2>
      <p class="mb-4">
        Data you enter into a shared family workspace (transactions, budgets, Zakat records, etc.)
        is visible to other members of that workspace according to their role. Review who you
        invite before adding sensitive financial details.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">5. Data retention and deletion</h2>
      <p class="mb-4">
        You can delete your account at any time from your Profile page (once you no longer own any
        family workspace). Deleting your account anonymizes your name and email and permanently
        disables login; some transaction records may be retained in anonymized form where needed
        to preserve the financial history of a family you belonged to.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">6. Security</h2>
      <p class="mb-4">
        Passwords are stored using industry-standard hashing. We use rate limiting, role-based
        access control, and encrypted connections (HTTPS) to protect your data. No method of
        storage or transmission is 100% secure, and we cannot guarantee absolute security.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">7. Your choices</h2>
      <p class="mb-4">
        You can access and edit most of your data directly in the app, export your family's
        records on request, unsubscribe from non-essential emails, and delete your account as
        described above.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">8. Changes to this policy</h2>
      <p class="mb-4">
        We may update this Privacy Policy from time to time. Material changes will be announced in
        the app or by email.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">9. Contact</h2>
      <p class="mb-4">
        For privacy questions or data requests, contact
        <a href="mailto:hello@hifzmaal.com" class="text-emerald-600 hover:underline">hello@hifzmaal.com</a>.
      </p>

      <p class="mt-10 text-xs text-gray-400 dark:text-slate-500">
        This document is a general template and has not been reviewed by a lawyer. Have it
        reviewed against GDPR/CCPA and your local data-protection law before relying on it
        commercially.
      </p>
    </main>
  </div>
</template>
