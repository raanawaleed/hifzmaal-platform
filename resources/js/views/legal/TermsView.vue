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
      <h1 class="mb-2 text-3xl font-black text-gray-900 dark:text-slate-100">Terms of Service</h1>
      <p class="mb-8 text-gray-400 dark:text-slate-500">Last updated: {{ lastUpdated }}</p>

      <p class="mb-6">
        These Terms of Service ("Terms") govern your use of HifzMaal, a family finance and Zakat
        management platform ("Service", "we", "us"). By creating an account, you agree to these
        Terms.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">1. Your account</h2>
      <p class="mb-4">
        You must provide accurate information and keep your login credentials secure. You are
        responsible for all activity under your account, including actions taken by family
        members you invite.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">2. Family workspaces</h2>
      <p class="mb-4">
        A family workspace is shared among its owner and invited members. The workspace owner
        controls who has access and what role each member holds. Removing a member or deleting a
        workspace is permanent and cannot be undone.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">3. Subscriptions and billing</h2>
      <p class="mb-4">
        Some features require a paid Pro subscription, billed in US dollars through Stripe on a
        recurring monthly or yearly basis. New accounts may include a free trial; if you do not
        subscribe before the trial ends, your account reverts to the Free plan and its limits
        apply. You may cancel at any time from the Billing page — your subscription remains active
        until the end of the current billing period, and we do not provide partial refunds for
        unused time except where required by law.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">4. Zakat calculations are informational</h2>
      <p class="mb-4">
        HifzMaal's Zakat calculator estimates Zakat due based on nisab thresholds and metal prices
        you or the platform configure. It is provided for convenience only and is
        <strong>not a substitute for guidance from a qualified Islamic scholar</strong>. You are
        responsible for verifying your Zakat obligations before making payments.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">5. Acceptable use</h2>
      <p class="mb-4">
        You agree not to use the Service for unlawful purposes, to attempt to access another
        user's data without authorization, or to disrupt the Service's operation.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">6. Account deletion</h2>
      <p class="mb-4">
        You may delete your account from your Profile page at any time, provided you no longer own
        any family workspace. Deletion cancels any active subscription and permanently removes
        your ability to log in; some transaction records may be retained in anonymized form to
        preserve the financial history of families you were a member of.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">7. Disclaimer and liability</h2>
      <p class="mb-4">
        The Service is provided "as is" without warranties of any kind. To the maximum extent
        permitted by law, we are not liable for indirect, incidental, or consequential damages
        arising from your use of the Service, including financial decisions made using data or
        calculations from the Service.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">8. Changes to these Terms</h2>
      <p class="mb-4">
        We may update these Terms from time to time. Continued use of the Service after a change
        means you accept the updated Terms.
      </p>

      <h2 class="mt-8 mb-2 text-lg font-bold text-gray-900 dark:text-slate-100">9. Contact</h2>
      <p class="mb-4">
        Questions about these Terms can be sent to
        <a href="mailto:hello@hifzmaal.com" class="text-emerald-600 hover:underline">hello@hifzmaal.com</a>
        or through our
        <router-link to="/contact" class="text-emerald-600 hover:underline">contact page</router-link>.
      </p>

      <p class="mt-10 text-xs text-gray-400 dark:text-slate-500">
        This document is a general template and has not been reviewed by a lawyer. Have it
        reviewed for your jurisdiction before relying on it commercially.
      </p>
    </main>
  </div>
</template>
