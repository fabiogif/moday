# Tasks

## 1. Shared registration form

- [x] 1.1 Make `register()` in `contexts/client-auth-context.tsx` return the created client and throw the first field error of a 422 (fallback to `message`); verify with a test that a 422 `errors.email` shows "Email já cadastrado nesta loja" instead of "Dados inválidos"
- [x] 1.2 Extract fields, masks, validations and submit from `store/[slug]/register/page.tsx` into `components/client-register-form.tsx` (`onSuccess(client)`, `onSubmittingChange`, `submitLabel`, children for extra actions); map a `fetch` `TypeError` to a connection message; verify with `src/__tests__/components/client-register-form.test.tsx` (success stores the session and calls `onSuccess`, 422 message, connection failure, mismatched passwords make no request)
- [x] 1.3 Render `ClientRegisterForm` in the register page with the login link as children and redirect on success; verify `store/[slug]/__tests__/auth-pages.test.tsx` still passes

## 2. Sign-up prompt on the menu

- [x] 2.1 Add `store/[slug]/components/signup-prompt-dialog.tsx`: question stage ("Deseja se cadastrar?", "Sim, quero me cadastrar", "Continuar sem cadastro") and form stage ("Voltar", "Continuar sem cadastro"); block closing while submitting; full-width stacked buttons and scrollable content for mobile
- [x] 2.2 In `store/[slug]/page.tsx`, move the body of `handleStartCheckout` to `proceedToCheckout()` unchanged; open the dialog after step-0 validation when not authenticated and not yet asked this visit; extract `applyClientPrefill` from the `auth/me` effect and use it for the registered client
- [x] 2.3 Update the existing page tests that tap "Continuar pedido" to answer "Continuar sem cadastro", mock `useClientAuth`, and add tests for: continue without registering, register and continue (prefilled, cart kept), API error (stays on form, data kept), e-mail already registered then continue, close keeps the cart, logged-in customer is not asked, asked once per visit

## 3. Verification

- [x] 3.1 Run the full frontend Jest suite (131 suites, 860 passed, 1 skipped), type-check and ESLint on the touched files with no new findings
- [x] 3.2 Check the flow in a real browser (headless Chromium) on the local store at 390×844, 768×1024 and 1366×800: dialog fits the screen, continue without registering, register and continue (prefilled, session saved), close keeps the cart, e-mail already registered then continue — 13/13 checks
- [x] 3.3 Fix found during the mobile check: the "Seu pedido" sheet clipped "Continuar pedido" below the screen (pre-existing); the order summary now scrolls and the actions stay pinned to the sheet footer — verified visible without scrolling at 390×844 and 320×568
- [x] 3.4 Fix found during the mobile check: the menu scrolled horizontally at 320px because the showcase cards' `sr-only` text (absolute) escaped the carousel's overflow; the carousel is now `relative` — page width equals the viewport at 320, 360, 375, 390, 414 and 768px
- [x] 3.5 Run `graphify update .` in `frontend/`
