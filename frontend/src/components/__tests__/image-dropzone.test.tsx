import { render, screen, fireEvent } from '@testing-library/react'
import { ImageDropzone } from '@/components/image-dropzone'
import { toast } from 'sonner'

jest.mock('sonner', () => ({
  toast: {
    error: jest.fn(),
    success: jest.fn(),
  },
}))

describe('ImageDropzone', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('renders drag and drop instructions', () => {
    render(<ImageDropzone onFileSelect={jest.fn()} />)

    expect(
      screen.getByText(/Arraste a imagem aqui ou clique para selecionar/i)
    ).toBeInTheDocument()
  })

  it('calls onFileSelect when a valid image is dropped', () => {
    const onFileSelect = jest.fn()
    render(<ImageDropzone onFileSelect={onFileSelect} />)

    const dropzone = screen.getByRole('button', {
      name: /Area para enviar imagem do produto|Área para enviar imagem do produto/i,
    })

    const file = new File(['fake'], 'produto.png', { type: 'image/png' })
    fireEvent.drop(dropzone, {
      dataTransfer: { files: [file] },
    })

    expect(onFileSelect).toHaveBeenCalledWith(file)
    expect(toast.error).not.toHaveBeenCalled()
  })

  it('rejects files larger than max size', () => {
    const onFileSelect = jest.fn()
    render(<ImageDropzone onFileSelect={onFileSelect} maxSize={10} />)

    const dropzone = screen.getByRole('button', {
      name: /Area para enviar imagem do produto|Área para enviar imagem do produto/i,
    })

    const file = new File(['01234567890123456789'], 'grande.png', {
      type: 'image/png',
    })
    fireEvent.drop(dropzone, {
      dataTransfer: { files: [file] },
    })

    expect(onFileSelect).not.toHaveBeenCalled()
    expect(toast.error).toHaveBeenCalled()
  })
})
