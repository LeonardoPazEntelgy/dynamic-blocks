const { useState, useEffect, createElement: el, Fragment } = wp.element;
const { TextControl, Button, SelectControl } = wp.components;
const { render } = wp.element;

const generateIndexJs = (shortName, title, category, icon) => {
  return `(function(blocks, blockEditor, element) {
  var el = element.createElement;
  var RichText = blockEditor.RichText;

  blocks.registerBlockType('dbb/${shortName}', {
    title: '${title}',
    icon: '${icon}',
    category: '${category}',
    attributes: {
      content: {
        type: 'string',
        source: 'html',
        selector: 'p'
      }
    },
    edit: function(props) {
      return el(RichText, {
        tagName: 'p',
        value: props.attributes.content,
        onChange: function(content) {
          props.setAttributes({ content: content });
        },
        placeholder: 'Escribe algo...'
      });
    },
    save: function(props) {
      return el(RichText.Content, {
        tagName: 'p',
        value: props.attributes.content
      });
    }
  });
})(
  window.wp.blocks,
  window.wp.blockEditor,
  window.wp.element
);`;
};

const categories = [
  { label: 'Widgets', value: 'widgets' },
  { label: 'Diseño', value: 'layout' },
  { label: 'Texto', value: 'text' },
  { label: 'Media', value: 'media' },
];

function IconSelector({ value, onChange }) {
  const [tab, setTab] = useState('dashicons');
  const dashicons = [
    "admin-appearance", "admin-collapse", "admin-comments", "admin-generic", "admin-home", "admin-links", "admin-media", "admin-network", "admin-page", "admin-plugins",
    "admin-post", "admin-settings", "admin-site", "admin-tools", "admin-users", "align-center", "align-full-width", "align-left", "align-none", "align-right",
    "analytics", "archive", "arrow-down", "arrow-down-alt", "arrow-down-alt2", "arrow-left", "arrow-left-alt", "arrow-left-alt2", "arrow-right", "arrow-right-alt",
    "arrow-right-alt2", "arrow-up", "arrow-up-alt", "arrow-up-alt2", "art", "awards", "backup", "book", "book-alt", "buddicons-activity", "buddicons-bbpress-logo",
    "buddicons-buddypress-logo", "buddicons-community", "buddicons-forums", "buddicons-friends", "buddicons-groups", "buddicons-pm", "buddicons-replies", "buddicons-topics",
    "buddicons-tracking", "building", "businessman", "businesswoman", "calendar", "camera", "carrot", "cart", "chart-area", "chart-bar", "chart-line", "chart-pie",
    "clipboard", "cloud", "cloud-saved", "cloud-upload", "controls-back", "controls-forward", "controls-pause", "controls-play", "controls-repeat", "controls-skipback",
    "controls-skipforward", "controls-volumeoff", "controls-volumeon", "dashboard", "database", "dismiss", "download", "editor-aligncenter", "editor-alignleft",
    "editor-alignright", "editor-bold", "editor-break", "editor-code", "editor-contract", "editor-customchar", "editor-expand", "editor-help", "editor-indent",
    "editor-insertmore", "editor-italic", "editor-kitchensink", "editor-ol", "editor-outdent", "editor-paragraph", "editor-paste-text", "editor-paste-word",
    "editor-quote", "editor-removeformatting", "editor-rtl", "editor-spellcheck", "editor-strikethrough", "editor-textcolor", "editor-ul", "editor-underline",
    "editor-unlink", "editor-video", "email", "email-alt", "exerpt-view", "external", "facebook", "facebook-alt", "feedback", "filter", "flag", "format-aside",
    "format-audio", "format-chat", "format-gallery", "format-image", "format-links", "format-quote", "format-standard", "format-status", "format-video", "forms",
    "googleplus", "grid-view", "groups", "hammer", "heading", "heart", "id", "id-alt", "image-crop", "image-filter", "image-flip-horizontal",
    "image-flip-vertical", "image-rotate-left", "image-rotate-right", "images-alt", "images-alt2", "index-card", "info", "info-outline", "insert", "insert-after",
    "insert-before", "instagram", "layout", "leftright", "lightbulb", "list-view", "location", "location-alt", "lock", "marker", "media-archive", "media-audio",
    "media-code", "media-default", "media-document", "media-interactive", "media-spreadsheet", "media-text", "media-video", "megaphone", "menu", "menu-alt",
    "menu-alt2", "menu-alt3", "microphone", "migrate", "minus", "money", "move", "nametag", "networking", "no", "palmtree", "performance", "pets",
    "phone", "playlist-audio", "playlist-video", "plus", "plus-alt", "portfolio", "post-status", "post-trash", "pressthis", "products", "randomize",
    "redo", "remove", "rest-api", "rss", "saved", "screenoptions", "search", "share", "share-alt", "share-alt2", "shield", "shield-alt", "shortcode", "slides",
    "smartphone", "smiley", "sort", "sos", "star-empty", "star-filled", "star-half", "sticky", "store", "superhero", "superhero-alt", "tablet", "tag", "tagcloud",
    "testimonial", "text", "tickets", "translation", "trash", "twitter", "twitter-alt", "undo", "universal-access", "unlock", "update", "update-alt", "upload",
    "vault", "video-alt", "video-alt2", "video-alt3", "visibility", "warning", "welcome-add-page", "welcome-comments", "welcome-learn-more", "welcome-view-site",
    "welcome-widgets-menus", "welcome-write-blog", "whatsapp", "wordpress", "yes"
  ];

  return el('div', { class: 'dbb-icon' },
    el('h3', { className: 'dbb-section-title' }, 'Icono de Bloque'),

    el('div', { style: { marginBottom: '10px' } },
      el('strong', null, 'Seleccionado: '),
      typeof value === 'string' && value.startsWith('<svg')
        ? el('span', {
            dangerouslySetInnerHTML: { __html: value },
            style: { width: '20px', height: '20px', display: 'inline-block', verticalAlign: 'middle' }
          })
        : typeof value === 'string' && value.startsWith('data:image/svg+xml')
        ? el('img', {
            src: value,
            style: { width: '20px', height: '20px', verticalAlign: 'middle' }
          })
        : el('span', {
            className: `dashicons dashicons-${value}`,
            style: { fontSize: '20px', verticalAlign: 'middle' }
          }),
      el('span', { style: { marginLeft: '8px', color: '#666' } }, value)
    ),

    el('div', { class: 'dbb-icon-tabs' },
      ['dashicons', 'upload', 'url'].map((t) =>
        el(Button, {
          key: t,
          isPressed: tab === t,
          onClick: () => setTab(t),
          style: { marginRight: '5px' }
        }, t.toUpperCase())
      )
    ),

    // Dashicons
    tab === 'dashicons' && el('div', { class: 'dbb-icon-grid'},
      dashicons.map((icon) =>
        el('div', {
          key: icon,
          class: 'dbb-icon-item',
          onClick: () => onChange(icon)
        },
          el('span', { className: `dashicons dashicons-${icon}` })
        )
      )
    ),

    tab === 'upload' && el('div', null,
      el('input', {
        type: 'file',
        accept: '.svg',
        onChange: (e) => {
          const file = e.target.files[0];
          if (!file || file.type !== 'image/svg+xml') {
            alert('Solo se permiten archivos SVG.');
            return;
          }

          const reader = new FileReader();
          reader.onload = (event) => {
            onChange(event.target.result); // base64 inline SVG
          };
          reader.readAsDataURL(file);
        }
      }),
      typeof value === 'string' && value.startsWith('data:image/svg+xml') && el('div', {
        style: { marginTop: '10px' }
      },
        el('strong', null, 'SVG cargado:'),
        el('img', {
          src: value,
          style: { width: '40px', height: 'auto', display: 'block', marginTop: '5px' }
        })
      )
    ),    

    // URL personalizada
    tab === 'url' && el(TextControl, {
      label: 'URL del ícono SVG',
      value: typeof value === 'string' && value.startsWith('http') ? value : '',
      onChange: onChange
    }),   
  );
}


function BlockCreator() {
  const [blockName, setBlockName] = useState('');
  const [blockTitle, setBlockTitle] = useState('');
  const [isChildBlock, setIsChildBlock] = useState(false);
  const [category, setCategory] = useState('widgets');
  const [icon, setIcon] = useState('smiley');
  const [status, setStatus] = useState('');
  const [blocks, setBlocks] = useState([]);
  const [selectedAcfGroup, setSelectedAcfGroup] = useState('');
  const [editAcfGroup, setEditAcfGroup] = useState('');
  const [template, setTemplate] = useState('default');
  const templates = [
    { label: 'Bloque normal (contenido editable o ACF)', value: 'default' },
    { label: 'Contenedor (InnerBlocks)', value: 'innerblocks' }
  ];

  // Edición
  const [editingBlock, setEditingBlock] = useState(null);
  const [editTitle, setEditTitle] = useState('');
  const [editCategory, setEditCategory] = useState('widgets');
  const [editIcon, setEditIcon] = useState('');

  useEffect(() => {
    fetchBlocks();
  }, []);

  const fetchBlocks = () => {
    wp.apiFetch({ path: '/dynamic-blocks/v1/list' }).then(setBlocks);
  };

  const handleCreateBlock = () => {
    if (!blockName || !blockTitle) {
      setStatus('⚠️ Por favor, completa todos los campos.');
      return;
    }

    wp.apiFetch({
      path: '/dynamic-blocks/v1/create',
      method: 'POST',
      data: { blockName, blockTitle, category, icon, acfGroup: selectedAcfGroup, template, isChildBlock }
    }).then((res) => {
      setStatus(`✅ Bloque "${res.name}" creado.`);
      setBlockName('');
      setBlockTitle('');
      fetchBlocks();
    }).catch((err) => {
      setStatus(`❌ Error: ${err.message}`);
    });
  };

  const handleDeleteBlock = (name) => {
    const shortName = name.replace(/^acf\//, '').replace(/^dbb[-/]/, '');
    if (!confirm(`¿Eliminar el bloque "${shortName}"?`)) return;

    wp.apiFetch({
      path: `/dynamic-blocks/v1/delete/${shortName}`,
      method: 'DELETE',
    }).then(() => {
      setStatus(`🗑️ Bloque "${shortName}" eliminado.`);
      fetchBlocks();
    }).catch((err) => {
      setStatus(`❌ Error al eliminar: ${err.message}`);
    });
  };

  const startEditingBlock = (name) => {
    const shortName = name.replace(/^acf\//, '').replace(/^dbb[-/]/, '');
    wp.apiFetch({ path: `/dynamic-blocks/v1/get/${shortName}` }).then((res) => {
      setEditingBlock(shortName);
      setEditTitle(res.blockJson.title);
      setEditCategory(res.blockJson.category);
      setEditIcon(res.blockJson.icon);
      setEditAcfGroup(res.blockJson.acfGroup || '');
    }).catch((err) => {
      setStatus(`❌ Error al cargar bloque: ${err.message}`);
    });
  };

  const saveEditingBlock = () => {
    const shortName = editingBlock.replace(/^acf\//, '').replace(/^dbb[-/]/, '');

    const indexJs = generateIndexJs(shortName, editTitle, editCategory, editIcon);

    wp.apiFetch({
      path: `/dynamic-blocks/v1/update/${shortName}`,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        title: editTitle,
        category: editCategory,
        icon: editIcon,
        indexJs: indexJs,
        acfGroup: editAcfGroup
      })
    }).then(() => {
      setStatus(`✅ Bloque "${editingBlock}" actualizado.`);
      setEditingBlock(null);
      fetchBlocks();
    }).catch((err) => {
      setStatus(`❌ Error al guardar: ${err.message}`);
      console.error(err);
    });
  };


  return el(Fragment, null,
    el('h1', null, 'Crear Bloque Dinámico'),
    el(TextControl, {
      label: 'Nombre técnico del bloque',
      value: blockName,
      onChange: setBlockName,
    }),
    el(TextControl, {
      label: 'Título visible del bloque',
      value: blockTitle,
      onChange: setBlockTitle,
    }),
    el(SelectControl, {
      label: 'Categoría',
      value: category,
      options: categories,
      onChange: setCategory,
    }),
    el(SelectControl, {
      label: 'Plantilla base',
      value: template,
      options: templates,
      onChange: setTemplate
    }),
    el('div', { style: { marginBottom: '10px' } },
      el('label', {},
        el('input', {
          type: 'checkbox',
          checked: isChildBlock,
          onChange: () => setIsChildBlock(!isChildBlock)
        }),
        ' Marcar como bloque hijo (reutilizable dentro de contenedores)'
      )
    ),    
    el(IconSelector, {
      value: icon,
      onChange: setIcon,
    }),

    el(Button, {
      variant: 'primary',
      onClick: handleCreateBlock,
    }, 'Crear bloque'),
    el('p', null, status),
    el('hr'),
    el('h2', null, 'Bloques existentes'),
    blocks.length === 0
      ? el('p', null, 'No hay bloques aún.')
      : el('ul', null,
        blocks.map((block) =>
          el('li', {
            key: block.name,
            style: { marginBottom: '10px' },
          },
            el('strong', null, block.title),
            ` (${block.name}) – Categoría: ${block.category} – Icono: ${block.icon} `,
            el(Button, {
              variant: 'secondary',
              style: { marginLeft: '10px' },
              onClick: () => startEditingBlock(block.name),
            }, 'Editar'),
            el(Button, {
              variant: 'destructive',
              style: { marginLeft: '5px' },
              onClick: () => handleDeleteBlock(block.name),
            }, 'Eliminar')
          )
        )
      ),

    // Modal de edición
    editingBlock && el('div', {
      style: {
        position: 'fixed',
        top: 0, left: 0, right: 0, bottom: 0,
        backgroundColor: 'rgba(0,0,0,0.5)',
        zIndex: 9999,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center'
      }
    },
      el('div', {
        style: {
          background: 'white',
          padding: '20px',
          width: '700px',
          maxHeight: '90%',
          overflowY: 'auto',
          borderRadius: '10px'
        }
      },
        el('h2', null, `Editar bloque: ${editingBlock}`),
        el(TextControl, {
          label: 'Título del bloque',
          value: editTitle,
          onChange: setEditTitle,
        }),
        el(SelectControl, {
          label: 'Categoría',
          value: editCategory,
          options: categories,
          onChange: setEditCategory,
        }),
        el(IconSelector, {
          value: editIcon,
          onChange: setEditIcon,
        }),
        el('div', { style: { marginTop: '15px' } },
          el(Button, {
            variant: 'primary',
            onClick: saveEditingBlock,
          }, 'Guardar cambios'),
          el(Button, {
            variant: 'secondary',
            style: { marginLeft: '10px' },
            onClick: () => setEditingBlock(null),
          }, 'Cancelar')
        )
      )
    )
  );
}

document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('dbb-admin-root');
  if (root) {
    render(el(BlockCreator), root);
  }
});
