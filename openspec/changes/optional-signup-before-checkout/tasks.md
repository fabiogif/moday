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
- [x] 3.2 Check the dialog in the local store on desktop (448px wide, no horizontal overflow). Mobile viewport check not completed: the browser froze while emulating 390px; mobile layout relies on the responsive classes and is pending a manual check
- [x] 3.3 Run `graphify update .` in `frontend/`
