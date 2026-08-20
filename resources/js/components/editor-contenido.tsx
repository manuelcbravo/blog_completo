import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import { EditorContent, useEditor } from '@tiptap/react';
import type { Editor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Code,
    Heading1,
    Heading2,
    Heading3,
    ImagePlus,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Quote,
    Redo2,
    Strikethrough,
    Undo2,
    Unlink,
} from 'lucide-react';
import { useEffect, useRef } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

type EditorContenidoProps = {
    value: string;
    onChange: (value: string) => void;
    onSubirImagen?: (archivo: File) => Promise<string | null>;
    placeholder?: string;
};

const CLASES_CONTENIDO = [
    'min-h-[28rem] w-full max-w-none px-4 py-3 text-sm leading-relaxed outline-none',
    '[&_h1]:mt-6 [&_h1]:mb-3 [&_h1]:text-2xl [&_h1]:font-semibold',
    '[&_h2]:mt-5 [&_h2]:mb-2 [&_h2]:text-xl [&_h2]:font-semibold',
    '[&_h3]:mt-4 [&_h3]:mb-2 [&_h3]:text-lg [&_h3]:font-semibold',
    '[&_p]:my-3',
    '[&_ul]:my-3 [&_ul]:list-disc [&_ul]:pl-6',
    '[&_ol]:my-3 [&_ol]:list-decimal [&_ol]:pl-6',
    '[&_blockquote]:my-4 [&_blockquote]:border-l-4 [&_blockquote]:border-border [&_blockquote]:pl-4 [&_blockquote]:text-muted-foreground',
    '[&_pre]:my-4 [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-muted [&_pre]:p-4 [&_pre]:text-xs',
    '[&_code]:rounded [&_code]:bg-muted [&_code]:px-1 [&_code]:py-0.5 [&_code]:text-xs',
    '[&_a]:text-primary [&_a]:underline [&_a]:underline-offset-4',
    '[&_img]:my-4 [&_img]:rounded-lg',
].join(' ');

export function EditorContenido({
    value,
    onChange,
    onSubirImagen,
    placeholder = 'Escribe el contenido de la publicación...',
}: EditorContenidoProps) {
    const inputArchivo = useRef<HTMLInputElement>(null);

    const editor = useEditor({
        extensions: [
            StarterKit,
            Link.configure({ openOnClick: false, autolink: true }),
            Image.configure({ inline: false }),
        ],
        content: value,
        editorProps: {
            attributes: {
                class: CLASES_CONTENIDO,
                'data-placeholder': placeholder,
            },
        },
        onUpdate: ({ editor }) => onChange(editor.getHTML()),
    });

    useEffect(() => {
        if (editor && value !== editor.getHTML()) {
            editor.commands.setContent(value, { emitUpdate: false });
        }
    }, [editor, value]);

    if (!editor) {
        return (
            <div className="h-[32rem] animate-pulse rounded-xl border border-sidebar-border/70 bg-muted/40" />
        );
    }

    const insertarEnlace = () => {
        const previo = editor.getAttributes('link').href as string | undefined;
        const url = window.prompt('URL del enlace', previo ?? 'https://');

        if (url === null) {
            return;
        }

        if (url === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();

            return;
        }

        editor
            .chain()
            .focus()
            .extendMarkRange('link')
            .setLink({ href: url })
            .run();
    };

    const elegirImagen = async (archivo: File | undefined) => {
        if (!archivo || !onSubirImagen) {
            return;
        }

        const url = await onSubirImagen(archivo);

        if (url) {
            editor.chain().focus().setImage({ src: url }).run();
        }
    };

    return (
        <div className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card">
            <div className="flex flex-wrap items-center gap-1 border-b border-sidebar-border/70 bg-sidebar-accent/20 p-2">
                <BotonEditor
                    editor={editor}
                    activo={editor.isActive('heading', { level: 1 })}
                    onClick={() =>
                        editor.chain().focus().toggleHeading({ level: 1 }).run()
                    }
                    titulo="Título 1"
                >
                    <Heading1 className="size-4" />
                </BotonEditor>
                <BotonEditor
                    editor={editor}
                    activo={editor.isActive('heading', { level: 2 })}
                    onClick={() =>
                        editor.chain().focus().toggleHeading({ level: 2 }).run()
                    }
                    titulo="Título 2"
                >
                    <Heading2 className="size-4" />
                </BotonEditor>
                <BotonEditor
                    editor={editor}
                    activo={editor.isActive('heading', { level: 3 })}
                    onClick={() =>
                        editor.chain().focus().toggleHeading({ level: 3 }).run()
                    }
                    titulo="Título 3"
                >
                    <Heading3 className="size-4" />
                </BotonEditor>

                <Separator orientation="vertical" className="mx-1 h-6" />

                <BotonEditor
                    editor={editor}
                    activo={editor.isActive('bold')}
                    onClick={() => editor.chain().focus().toggleBold().run()}
                    titulo="Negrita"
                >
                    <Bold className="size-4" />
                </BotonEditor>
                <BotonEditor
                    editor={editor}
                    activo={editor.isActive('italic')}
                    onClick={() => editor.chain().focus().toggleItalic().run()}
                    titulo="Cursiva"
                >
                    <Italic className="size-4" />
                </BotonEditor>
                <BotonEditor
                    editor={editor}
                    activo={editor.isActive('strike')}
                    onClick={() => editor.chain().focus().toggleStrike().run()}
                    titulo="Tachado"
                >
                    <Strikethrough className="size-4" />
                </BotonEditor>
                <BotonEditor
                    editor={editor}
                    activo={editor.isActive('codeBlock')}
                    onClick={() =>
                        editor.chain().focus().toggleCodeBlock().run()
                    }
                    titulo="Bloque de código"
                >
                    <Code className="size-4" />
                </BotonEditor>

                <Separator orientation="vertical" className="mx-1 h-6" />

                <BotonEditor
                    editor={editor}
                    activo={editor.isActive('bulletList')}
                    onClick={() =>
                        editor.chain().focus().toggleBulletList().run()
                    }
                    titulo="Lista"
                >
                    <List className="size-4" />
                </BotonEditor>
                <BotonEditor
                    editor={editor}
                    activo={editor.isActive('orderedList')}
                    onClick={() =>
                        editor.chain().focus().toggleOrderedList().run()
                    }
                    titulo="Lista numerada"
                >
                    <ListOrdered className="size-4" />
                </BotonEditor>
                <BotonEditor
                    editor={editor}
                    activo={editor.isActive('blockquote')}
                    onClick={() =>
                        editor.chain().focus().toggleBlockquote().run()
                    }
                    titulo="Cita"
                >
                    <Quote className="size-4" />
                </BotonEditor>

                <Separator orientation="vertical" className="mx-1 h-6" />

                <BotonEditor
                    editor={editor}
                    activo={editor.isActive('link')}
                    onClick={insertarEnlace}
                    titulo="Enlace"
                >
                    <LinkIcon className="size-4" />
                </BotonEditor>
                <BotonEditor
                    editor={editor}
                    activo={false}
                    onClick={() => editor.chain().focus().unsetLink().run()}
                    titulo="Quitar enlace"
                >
                    <Unlink className="size-4" />
                </BotonEditor>
                {onSubirImagen && (
                    <BotonEditor
                        editor={editor}
                        activo={false}
                        onClick={() => inputArchivo.current?.click()}
                        titulo="Insertar imagen"
                    >
                        <ImagePlus className="size-4" />
                    </BotonEditor>
                )}

                <div className="ml-auto flex items-center gap-1">
                    <BotonEditor
                        editor={editor}
                        activo={false}
                        onClick={() => editor.chain().focus().undo().run()}
                        titulo="Deshacer"
                    >
                        <Undo2 className="size-4" />
                    </BotonEditor>
                    <BotonEditor
                        editor={editor}
                        activo={false}
                        onClick={() => editor.chain().focus().redo().run()}
                        titulo="Rehacer"
                    >
                        <Redo2 className="size-4" />
                    </BotonEditor>
                </div>
            </div>

            <EditorContent editor={editor} />

            <input
                ref={inputArchivo}
                type="file"
                accept="image/*"
                className="hidden"
                onChange={(event) => {
                    void elegirImagen(event.target.files?.[0]);
                    event.target.value = '';
                }}
            />
        </div>
    );
}

function BotonEditor({
    activo,
    onClick,
    titulo,
    children,
}: {
    editor: Editor;
    activo: boolean;
    onClick: () => void;
    titulo: string;
    children: ReactNode;
}) {
    return (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            title={titulo}
            aria-label={titulo}
            aria-pressed={activo}
            className={cn('size-8', activo && 'bg-background shadow-sm')}
            onClick={onClick}
        >
            {children}
        </Button>
    );
}
