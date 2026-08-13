import { render, screen, waitFor, act } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { ProductFormWizard, ProductFormValues } from '../components/product-form-wizard'

// jsdom não implementa scrollIntoView; o Combobox (cmdk) chama isso internamente ao navegar as opções
beforeAll(() => {
  Element.prototype.scrollIntoView = jest.fn()
})

const productFormSchema = z.object({
  name: z.string().min(2, 'Nome deve ter pelo menos 2 caracteres.'),
  description: z.string().min(5, 'Descrição deve ter pelo menos 5 caracteres.'),
  price: z.number().min(0),
  price_cost: z.number().min(0),
  promotional_price: z.number().min(0).optional(),
  brand: z.string().optional(),
  sku: z.string().optional(),
  weight: z.number().min(0).optional(),
  height: z.number().min(0).optional(),
  width: z.number().min(0).optional(),
  depth: z.number().min(0).optional(),
  shipping_info: z.string().optional(),
  warehouse_location: z.string().optional(),
  categories: z.array(z.string()).min(1, 'Por favor, selecione pelo menos uma categoria.'),
  qtd_stock: z.number().min(0),
  image: z.any().optional(),
  is_active: z.boolean().optional(),
})

function Harness({ onSubmit, mode = 'create' as const }: { onSubmit: jest.Mock; mode?: 'create' | 'edit' }) {
  const form = useForm<ProductFormValues>({
    resolver: zodResolver(productFormSchema),
    defaultValues: {
      name: '',
      description: '',
      price: 0,
      price_cost: 0,
      categories: [],
      qtd_stock: 0,
    },
  })

  return (
    <ProductFormWizard
      mode={mode}
      title="Novo Produto"
      description="Adicione um novo produto"
      form={form}
      categoryOptions={[{ value: 'cat-1', label: 'Bebidas' }]}
      variations={[]}
      onVariationsChange={jest.fn()}
      optionals={[]}
      onOptionalsChange={jest.fn()}
      currentImage={null}
      onImageChange={jest.fn()}
      submitting={false}
      submitLabel="Criar Produto"
      onSubmit={onSubmit}
      onCancel={jest.fn()}
    />
  )
}

describe('ProductFormWizard - wizard de passos', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  test('renders only step 1 fields on open', () => {
    render(<Harness onSubmit={jest.fn()} />)

    expect(screen.getByLabelText(/Nome do Produto/i)).toBeInTheDocument()
    expect(screen.queryByLabelText(/Preço de Venda/i)).not.toBeInTheDocument()
    expect(screen.queryByLabelText(/Peso \(kg\)/i)).not.toBeInTheDocument()
  })

  test('keeps Continuar disabled until name, description and category are filled', async () => {
    const user = userEvent.setup()
    render(<Harness onSubmit={jest.fn()} />)

    const continueBtn = screen.getByRole('button', { name: /Continuar/i })
    expect(continueBtn).toBeDisabled()

    await user.type(screen.getByLabelText(/Nome do Produto/i), 'Pizza Margherita')
    await user.type(screen.getByLabelText(/Descrição/i), 'Molho, mussarela e manjericão')
    expect(continueBtn).toBeDisabled() // ainda falta categoria

    await user.click(screen.getByRole('combobox'))
    await user.click(await screen.findByText('Bebidas'))

    expect(continueBtn).toBeEnabled()
  })

  test('advances through all steps and submits collected data', async () => {
    const user = userEvent.setup()
    const onSubmit = jest.fn()
    render(<Harness onSubmit={onSubmit} />)

    await user.type(screen.getByLabelText(/Nome do Produto/i), 'Pizza Margherita')
    await user.type(screen.getByLabelText(/Descrição/i), 'Molho, mussarela e manjericão')
    await user.click(screen.getByRole('combobox'))
    await user.click(await screen.findByText('Bebidas'))
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    // Passo 2: Preços e Estoque
    expect(await screen.findByLabelText(/Preço de Venda/i)).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    // Passo 3: Logística (opcional)
    expect(await screen.findByLabelText(/Peso \(kg\)/i)).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    // Passo 4: Imagem (opcional)
    expect(await screen.findByText(/Sem imagem cadastrada/i)).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    // Passo 5: Variações e Opcionais
    expect(await screen.findByText('Variações do Produto')).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    // Passo 6: Revisão
    expect(await screen.findByText(/Confira os dados antes de salvar/i)).toBeInTheDocument()
    await act(async () => {
      await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)))
    })
    await user.click(screen.getByRole('button', { name: /Criar Produto/i }))

    expect(onSubmit).toHaveBeenCalled()
    expect(onSubmit.mock.calls[0][0]).toEqual(
      expect.objectContaining({
        name: 'Pizza Margherita',
        categories: ['cat-1'],
      })
    )
  })

    test('does not auto-save when continuing from Imagem to Variacoes', async () => {
    const user = userEvent.setup()
    const onSubmit = jest.fn()
    render(<Harness onSubmit={onSubmit} mode="edit" />)

    await user.type(screen.getByLabelText(/Nome do Produto/i), 'Pizza Margherita')
    await user.type(screen.getByLabelText(/Descri/i), 'Molho, mussarela e manjericao')
    await user.click(screen.getByRole('combobox'))
    await user.click(await screen.findByText('Bebidas'))
    await user.click(screen.getByRole('button', { name: /Continuar/i }))
    await user.click(screen.getByRole('button', { name: /Continuar/i }))
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    // Passo Imagem
    expect(await screen.findByText(/Sem imagem cadastrada/i)).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    // Passo variações: ainda Continuar, sem submit automático
    expect(await screen.findByText('Variações do Produto')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Continuar/i })).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
    await user.click(screen.getByRole('button', { name: /Continuar/i }))

    expect(await screen.findByRole('button', { name: /Criar Produto/i })).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /Continuar/i })).not.toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })

  test('shows the "Produto Ativo" toggle only in edit mode', async () => {
    render(<Harness onSubmit={jest.fn()} mode="edit" />)
    expect(screen.getByText(/Produto Ativo/i)).toBeInTheDocument()
  })

  test('hides the "Produto Ativo" toggle in create mode', () => {
    render(<Harness onSubmit={jest.fn()} mode="create" />)
    expect(screen.queryByText(/Produto Ativo/i)).not.toBeInTheDocument()
  })

  test('review step offers edit links back to previous sections', async () => {
    const user = userEvent.setup()
    render(<Harness onSubmit={jest.fn()} />)

    await user.type(screen.getByLabelText(/Nome do Produto/i), 'Pizza Margherita')
    await user.type(screen.getByLabelText(/Descrição/i), 'Molho, mussarela e manjericão')
    await user.click(screen.getByRole('combobox'))
    await user.click(await screen.findByText('Bebidas'))

    for (let i = 0; i < 5; i++) {
      await user.click(screen.getByRole('button', { name: /Continuar/i }))
    }

    expect(await screen.findByText(/Confira os dados antes de salvar/i)).toBeInTheDocument()
    await user.click(screen.getAllByRole('button', { name: /Editar/i })[0])
    expect(await screen.findByLabelText(/Nome do Produto/i)).toBeInTheDocument()
    expect(screen.getByDisplayValue('Pizza Margherita')).toBeInTheDocument()
  })

  test('restores a saved draft on create', async () => {
    localStorage.setItem(
      'product-form-draft',
      JSON.stringify({
        step: 1,
        values: {
          name: 'Hambúrguer',
          description: 'Pão, carne e queijo',
          categories: ['cat-1'],
          price: 20,
          price_cost: 10,
          qtd_stock: 5,
        },
        variations: [],
        optionals: [],
      }),
    )

    render(<Harness onSubmit={jest.fn()} />)

    expect(await screen.findByText(/Rascunho restaurado/i)).toBeInTheDocument()
    expect(await screen.findByLabelText(/Preço de Venda/i)).toBeInTheDocument()
  })
})
