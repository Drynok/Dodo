<?php

namespace domo\algorithm

/*
 * Why are these here?
 * They're here because this is where they make sense.
 */

/* https://dom.spec.whatwg.org/#dom-node-comparedocumentposition */
static function _DOM_compare_document_position(Node $node1, Node $node2): integer
{
        /* #1-#2 */
        if ($node1 === $node2) {
                return 0;
        }

        /* #3 */
        $attr1 = NULL;
        $attr2 = NULL;

        /* #4 */
        if ($node1->_nodeType === ATTRIBUTE_NODE) {
                $attr1 = $node1;
                $node1 = $attr1->ownerElement();
        }
        /* #5 */
        if ($node2->_nodeType === ATTRIBUTE_NODE) {
                $attr2 = $node2;
                $node2 = $attr2->ownerElement();

                if ($attr1 !== NULL && $node1 !== NULL && $node2 === $node1) {
                        foreach ($node2->attributes as $a) {
                                if ($a === $attr1) {
                                        return DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC + DOCUMENT_POSITION_PRECEDING;
                                }
                                if ($a === $attr2) {
                                        return DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC + DOCUMENT_POSITION_FOLLOWING;
                                }
                        }
                }
        }

        /* #6 */
        if ($node1 === NULL || $node2 === NULL || $node1->__node_document() !== $node2->__node_document() || $node1->__is_rooted() !== $node2->__is_rooted()) {
                /* UHH, in the spec this is supposed to add DOCUMENT_POSITION_PRECEDING or DOCUMENT_POSITION_FOLLOWING
                 * in some consistent way, usually based on pointer comparison, which we can't do here. Hmm. Domino
                 * just straight up omits it. This is stupid, the spec shouldn't ask this. */
                return (DOCUMENT_POSITION_DISCONNECTED + DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC);
        }

        /* #7 */
        $node1_ancestors = array();
        $node2_ancestors = array();
        for ($n = $node1->parentNode(); $n !== NULL; $n = $n->parentNode()) {
                $node1_ancestors[] = $n;
        }
        for ($n = $node2->parentNode(); $n !== NULL; $n = $n->parentNode()) {
                $node2_ancestors[] = $n;
        }

        if (in_array($node1, $node2_ancestors) && $attr1 === NULL) {
                return DOCUMENT_POSITION_CONTAINS + DOCUMENT_POSITION_PRECEDING;
        } else if ($node1 === $node2 && $attr2 !== NULL) {
                return DOCUMENT_POSITION_CONTAINS + DOCUMENT_POSITION_PRECEDING;
        }

        /* #8 */
        if (in_array($node2, $node1_ancestors) && $attr2 === NULL) {
                return DOCUMENT_POSITION_CONTAINED_BY + DOCUMENT_POSITION_FOLLOWING;
        } else if ($node1 === $node2 && $attr1 !== NULL) {
                return DOCUMENT_POSITION_CONTAINED_BY + DOCUMENT_POSITION_FOLLOWING;
        }

        /* #9 */
        $node1_ancestors = array_reverse($node1_ancestors);
        $node2_ancestors = array_reverse($node2_ancestors);
        $len = min(count($node1_ancestors), count($node2_ancestors));

        for ($i = 1; $i < $len; $i++) {
                if ($node1_ancestors[$i] !== $node2_ancestors[$i]) {
                        if ($node1_ancestors[$i]->index() < $node2_ancestors[$i]->index()) {
                                return DOCUMENT_POSITION_PRECEDING;
                        }
                }
        }

        #10
        return DOCUMENT_POSITION_FOLLOWING;
}

/*
 * DOM-LS Removes the 'prefix' and 'namespaceURI' attributes from
 * Node and places them only on Element and Attr.
 *
 * Due to the fact that an Attr (should) have an ownerElement,
 * these two algorithms only operate on Elements.
 *
 * The spec actually says that if an Attr has no ownerElement,
 * then the algorithm returns NULL.
 *
 * Anyway, they operate only on Elements.
 */

/* https://dom.spec.whatwg.org/#locate-a-namespace */
static function _DOM_locate_namespace(Node $node, ?string $prefix): ?string
{
        if ($prefix === '') {
                $prefix = NULL;
        }

        switch ($this->_nodeType) {
        case ENTITY_NODE:
        case NOTATION_NODE:
        case DOCUMENT_TYPE_NODE:
        case DOCUMENT_FRAGMENT_NODE:
                break;
        case ELEMENT_NODE:
                if ($node->namespaceURI()!==NULL && $node->prefix()===$prefix) {
                        return $node->namespaceURI();
                }
                foreach ($node->attributes as $a) {
                        if ($a->namespaceURI() === NAMESPACE_XMLNS) {
                                if (($a->prefix() === 'xmlns' && $a->localName() === $prefix)
                                ||  ($prefix === NULL && $a->prefix() === NULL && $a->localName() === 'xmlns') {
                                        $val = $a->value();
                                        return ($val === "") ? NULL : $val;
                                }
                        }
                }
                break;
        case DOCUMENT_NODE:
                if ($this->_documentElement) {
                        return _DOM_locate_namespace($this->_documentElement, $prefix);
                }
                break;
        case ATTRIBUTE_NODE:
                if ($this->_ownerElement) {
                        return _DOM_locate_namespace($this->_ownerElement, $prefix);
                }
               break;
        default:
                if (NULL === ($parent = $node->parentElement())) {
                        return NULL;
                } else {
                        return _DOM_locate_namespace($parent, $ns);
                }
        }

        return NULL;
}

/* https://dom.spec.whatwg.org/#locate-a-namespace-prefix */
static function _DOM_locate_prefix(Node $node, ?string $ns): ?string
{
        if ($ns === "" || $ns === NULL) {
                return NULL;
        }

        switch ($node->_nodeType) {
        case ENTITY_NODE:
        case NOTATION_NODE:
        case DOCUMENT_FRAGMENT_NODE:
        case DOCUMENT_TYPE_NODE:
                break;
        case ELEMENT_NODE:
                if ($node->namespaceURI()!==NULL && $node->namespaceURI()===$ns) {
                        return $node->prefix();
                }

                foreach ($node->attributes as $a) {
                        if ($a->prefix() === "xmlns" && $a->value() === $ns) {
                                return $a->localName();
                        }
                }
                break
        case DOCUMENT_NODE:
                if ($node->_documentElement) {
                        return _DOM_locate_prefix($node->_documentElement, $ns);
                }
                break;
        case  ATTRIBUTE_NODE:
                if ($node->_ownerElement) {
                        return _DOM_locate_prefix($node->_ownerElement, $ns);
                }
                break;
        default:
                if (NULL === ($parent = $node->parentElement())) {
                        return NULL;
                } else {
                        return _DOM_locate_prefix($parent, $ns);
                }
        }

        return NULL;
}


static function _DOM_insertBeforeOrReplace(Node $node, Node $parent, ?Node $before, boolean $replace): void
{
        /* 
         * TODO: FACTOR: $ref_node is intended to always be non-NULL 
         * if $isReplace is true, but I think that could fail.
         */

        /******************* PRE-FLIGHT CHECKS *******************/

        if ($node === $before) {
                return;
        }

        if ($node instanceof DocumentFragment && $node->__is_rooted()) {
                \domo\error("HierarchyRequestError");
        }

        /******************** COMPUTE AN INDEX *******************/
        /* NOTE: MUST DO HERE BECAUSE STATE WILL CHANGE */

        if ($parent->_childNodes) {
                if ($before !== NULL) {
                        $ref_index = $before->index();
                } else {
                        $ref_index = count($parent->_childNodes);
                }
                if ($node->_parentNode===$parent && $node->index()<$ref_index) {
                        $ref_index--;
                }
        }

        $ref_node = $before ?? $parent->firstChild();

        /************ IF REPLACING, REMOVE OLD CHILD *************/

        if ($replace) {
                if ($before->__is_rooted()) {
                        $before->__node_document()->__mutate_remove($before);
                        $before->__uproot();
                }
                $before->_parentNode = NULL;
        }

        /************ IF BOTH ROOTED, FIRE MUTATIONS *************/

        $bothWereRooted = $node->__is_rooted() && $parent->__is_rooted();

        if ($bothWereRooted) {
                /* "soft remove" -- don't want to uproot it. */
                $node->_remove();
        } else {
                if ($node->_parentNode) {
                        $node->remove();
                }
        }

        /************** UPDATE THE NODE LIST DATA ***************/

        $insert = array();

        if ($node instanceof DocumentFragment) {
                for ($n=$node->firstChild(); $n!==NULL; $n=$n->nextSibling()) {
                        $insert[] = $n; /* TODO: Needs to clone? */
                        $n->_parentNode = $parent;
                }
        } else {
                $insert[0] = $node; /* TODO: Needs to clone? */
                $insert[0]->_parentNode = $parent;
        }

        if (empty($insert)) {
                if ($replace) {
                        if ($ref_node !== NULL /* If you work it out, you'll find that this condition is equivalent to 'if $parent has children' */) {
                                LinkedList\replace($ref_node, NULL);
                        }
                        if ($parent->_childNodes === NULL && $parent->_firstChild === $before) {
                                $parent->_firstChild = NULL;
                        }
                }
        } else {
                if ($ref_node !== NULL) {
                        if ($replace) {
                                LinkedList\replace($ref_node, $insert[0]);
                        } else {
                                LinkedList\insertBefore($insert[0], $ref_node);
                        }
                }
                if ($parent->_childNodes !== NULL) {
                        if ($replace) {
                                array_splice($parent->_childNodes, $ref_index, 1, $insert);
                        } else {
                                array_splice($parent->_childNodes, $ref_index, 0, $insert);
                        }
                        foreach ($insert as $i => $n) {
                                $n->_index = $ref_index + $i;
                        }
                } else if ($parent->_firstChild === $before) {
                        $parent->_firstChild = $insert[0];
                }
        }

        /*********** EMPTY OUT THE DOCUMENT FRAGMENT ************/

        if ($node instanceof DocumentFragment) {
                /* 
                 * TODO: Why? SPEC SAYS SO!
                 */
                if ($node->_childNodes) {
                        /* TODO PORT: easiest way to do this in PHP and preserves references */
                        $node->_childNodes = array();
                } else {
                        $node->_firstChild = NULL;
                }
        }

        /************ ROOT NODES AND FIRE MUTATION HANDLERS *************/

        $d = $parent->nodeDocument();

        if ($bothWereRooted) {
                $parent->__lastmod_update(); 
                $d->__mutate_move($insert[0]); 
        } else {
                if ($parent->__is_rooted()) {
                        $parent->__lastmod_update(); 
                        foreach ($insert as $n) {
                                $n->__root($d);
                                $d->__mutate_insert($n);
                        }
                }
        }
}

/*
TODO: Look at the way these were implemented in the original;
there are some speedups esp in the way that you implement
things like "node has a doctype child that is not child
*/
static function _DOM_ensureInsertValid(Node $node, Node $parent, ?Node $child): void
{
        /*
         * DOM-LS: #1: If parent is not a Document, DocumentFragment,
         * or Element node, throw a HierarchyRequestError.
         */
        switch ($parent->_nodeType) {
        case DOCUMENT_NODE:
        case DOCUMENT_FRAGMENT_NODE:
        case ELEMENT_NODE:
                break;
        default:
                \domo\error("HierarchyRequestError");
        }

        /*
         * DOM-LS #2: If node is a host-including inclusive ancestor
         * of parent, throw a HierarchyRequestError.
         */
        if ($node === $parent) {
                \domo\error("HierarchyRequestError");
        }
        if ($node->__node_document() === $parent->__node_document() && $node->__is_rooted() === $parent->__is_rooted()) {
                /* 
                 * If the conditions didn't figure it out, then check
                 * by traversing parentNode chain. 
                 */
                for ($n=$parent; $n!==NULL; $n=$n->parentNode()) {
                        if ($n === $node) {
                                \domo\error("HierarchyRequestError");
                        }
                }
        }

        /*
         * DOM-LS #3: If child is not null and its parent is not $parent, then
         * throw a NotFoundError
         */
        if ($child !== NULL && $child->_parentNode !== $parent) {
                \domo\error("NotFoundError");
        }

        /*
         * DOM-LS #4: If node is not a DocumentFragment, DocumentType,
         * Element, Text, ProcessingInstruction, or Comment Node,
         * throw a HierarchyRequestError.
         */
        switch ($node->_nodeType) {
        case DOCUMENT_FRAGMENT_NODE:
        case DOCUMENT_TYPE_NODE:
        case ELEMENT_NODE:
        case TEXT_NODE:
        case PROCESSING_INSTRUCTION_NODE:
        case COMMENT_NODE:
                break;
        default:
                error("HierarchyRequestError");
        }

        /*
         * DOM-LS #5. If either:
         *      -node is a Text and parent is a Document
         *      -node is a DocumentType and parent is not a Document
         * throw a HierarchyRequestError
         */
        if (($node->_nodeType === TEXT_NODE          && $parent->_nodeType === DOCUMENT_NODE)
        ||  ($node->_nodeType === DOCUMENT_TYPE_NODE && $parent->_nodeType !== DOCUMENT_NODE)) {
                \domo\error("HierarchyRequestError");
        }

        /*
         * DOM-LS #6: If parent is a Document, and any of the
         * statements below, switched on node, are true, throw a
         * HierarchyRequestError.
         */
        if ($parent->_nodeType !== DOCUMENT_NODE) {
                return;
        }

        switch ($node->_nodeType) {
        case DOCUMENT_FRAGMENT_NODE:
                /*
                 * DOM-LS #6a-1: If node has more than one
                 * Element child or has a Text child.
                 */
                $count_text = 0;
                $count_element = 0;

                for ($n=$node->firstChild(); $n!==NULL; $n=$n->nextSibling()) {
                        if ($n->_nodeType === TEXT_NODE) {
                                $count_text++;
                        }
                        if ($n->_nodeType === ELEMENT_NODE) {
                                $count_element++;
                        }
                        if ($count_text > 0 && $count_element > 1) {
                                \domo\error("HierarchyRequestError");
                                // TODO: break ? return ?
                        }
                }
                /*
                 * DOM-LS #6a-2: If node has one Element
                 * child and either:
                 */
                if ($count_element === 1) {
                        /* DOM-LS #6a-2a: child is a DocumentType */
                        if ($child !== NULL && $child->_nodeType === DOCUMENT_TYPE_NODE) {
                               \domo\error("HierarchyRequestError");
                        }
                        /*
                         * DOM-LS #6a-2b: child is not NULL and a
                         * DocumentType is following child.
                         */
                        if ($child !== NULL) {
                                for ($n=$child->nextSibling(); $n!==NULL; $n=$n->nextSibling()) {
                                        if ($n->_nodeType === DOCUMENT_TYPE_NODE) {
                                                \domo\error("HierarchyRequestError");
                                        }
                                }
                        }
                        /* DOM-LS #6a-2c: parent has an Element child */
                        for ($n=$parent->firstChild(); $n!==NULL; $n=$n->nextSibling()) {
                                if ($n->_nodeType === ELEMENT_NODE) {
                                        \domo\error("HierarchyRequestError");
                                }
                        }
                }
                break;
        case ELEMENT_NODE:
                /* DOM-LS #6b-1: child is a DocumentType */
                if ($child !== NULL && $child->_nodeType === DOCUMENT_TYPE_NODE) {
                       \domo\error("HierarchyRequestError");
                }
                /* DOM-LS #6b-2: child not NULL and DocumentType is following child. */
                if ($child !== NULL) {
                        for ($n=$child->nextSibling(); $n!==NULL; $n=$n->nextSibling()) {
                                if ($n->_nodeType === DOCUMENT_TYPE_NODE) {
                                        \domo\error("HierarchyRequestError");
                                }
                        }
                }
                /* DOM-LS #6b-3: parent has an Element child */
                for ($n=$parent->firstChild(); $n!==NULL; $n=$n->nextSibling()) {
                        if ($n->_nodeType === ELEMENT_NODE) {
                                \domo\error("HierarchyRequestError");
                        }
                }
                break;
        case DOCUMENT_TYPE_NODE:
                /* DOM-LS #6c-1: parent has a DocumentType child */
                for ($n=$parent->firstChild(); $n!==NULL; $n=$n->nextSibling()) {
                        if ($n->_nodeType === DOCUMENT_TYPE_NODE) {
                                \domo\error("HierarchyRequestError");
                        }
                }
                /*
                 * DOM-LS #6c-2: child is not NULL and an Element
                 * is preceding child,
                 */
                if ($child !== NULL) {
                        for ($n=$child->previousSibling(); $n!==NULL; $n=$n->previousSibling()) {
                                if ($n->_nodeType === ELEMENT_NODE) {
                                        \domo\error("HierarchyRequestError");
                                }
                        }
                }
                /*
                 * DOM-LS #6c-3: child is NULL and parent has
                 * an Element child.
                 */
                if ($child === NULL) {
                        for ($n=$parent->firstChild(); $n!==NULL; $n=$n->nextSibling()) {
                                if ($n->_nodeType === ELEMENT_NODE) {
                                        \domo\error("HierarchyRequestError");
                                }
                        }
                }

                break;
        }
}

static function _DOM_ensureReplaceValid(Node $node, Node $parent, Node $child): void
{
        /*
         * DOM-LS: #1: If parent is not a Document, DocumentFragment,
         * or Element node, throw a HierarchyRequestError.
         */
        switch ($parent->nodeType) {
        case DOCUMENT_NODE:
        case DOCUMENT_FRAGMENT_NODE:
        case ELEMENT_NODE:
                break;
        default:
                error("HierarchyRequestError");
        }

        /*
         * DOM-LS #2: If node is a host-including inclusive ancestor
         * of parent, throw a HierarchyRequestError.
         */
        if ($node === $parent) {
                \domo\error("HierarchyRequestError");
        }
        if ($node->__node_document() === $parent->__node_document() && $node->__is_rooted() === $parent->__is_rooted()) {
                /* 
                 * If the conditions didn't figure it out, then check
                 * by traversing parentNode chain. 
                 */
                for ($n=$parent; $n!==NULL; $n=$n->parentNode()) {
                        if ($n === $node) {
                                \domo\error("HierarchyRequestError");
                        }
                }
        }

        /*
         * DOM-LS #3: If child's parentNode is not parent
         * throw a NotFoundError
         */
        if ($child->_parentNode !== $parent) {
                error("NotFoundError");
        }

        /*
         * DOM-LS #4: If node is not a DocumentFragment, DocumentType,
         * Element, Text, ProcessingInstruction, or Comment Node,
         * throw a HierarchyRequestError.
         */
        switch ($node->_nodeType) {
        case DOCUMENT_FRAGMENT_NODE:
        case DOCUMENT_TYPE_NODE:
        case ELEMENT_NODE:
        case TEXT_NODE:
        case PROCESSING_INSTRUCTION_NODE:
        case COMMENT_NODE:
                break;
        default:
                \domo\error("HierarchyRequestError");
        }

        /*
         * DOM-LS #5. If either:
         *      -node is a Text and parent is a Document
         *      -node is a DocumentType and parent is not a Document
         * throw a HierarchyRequestError
         */
        if (($node->_nodeType === TEXT_NODE          && $parent->_nodeType === DOCUMENT_NODE)
        ||  ($node->_nodeType === DOCUMENT_TYPE_NODE && $parent->_nodeType !== DOCUMENT_NODE)) {
                \domo\error("HierarchyRequestError");
        }

        /*
         * DOM-LS #6: If parent is a Document, and any of the
         * statements below, switched on node, are true, throw a
         * HierarchyRequestError.
         */
        if ($parent->_nodeType !== DOCUMENT_NODE) {
                return;
        }

        switch ($node->_nodeType) {
        case DOCUMENT_FRAGMENT_NODE:
                /*
                 * #6a-1: If node has more than one Element child
                 * or has a Text child.
                 */
                $count_text = 0;
                $count_element = 0;

                for ($n=$node->firstChild(); $n!==NULL; $n=$n->nextSibling()) {
                        if ($n->_nodeType === TEXT_NODE) {
                                $count_text++;
                        }
                        if ($n->_nodeType === ELEMENT_NODE) {
                                $count_element++;
                        }
                        if ($count_text > 0 && $count_element > 1) {
                                \domo\error("HierarchyRequestError");
                        }
                }
                /* #6a-2: If node has one Element child and either: */
                if ($count_element === 1) {
                        /* #6a-2a: parent has an Element child that is not child */
                        for ($n=$parent->firstChild(); $n!==NULL; $n=$n->nextSibling()) {
                                if ($n->_nodeType === ELEMENT_NODE && $n !== $child) {
                                        \domo\error("HierarchyRequestError");
                                }
                        }
                        /* #6a-2b: a DocumentType is following child. */
                        for ($n=$child->nextSibling(); $n!==NULL; $n=$n->nextSibling()) {
                                if ($n->_nodeType === DOCUMENT_TYPE_NODE) {
                                        \domo\error("HierarchyRequestError");
                                }
                        }
                }
                break;
        case ELEMENT_NODE:
                /* #6b-1: parent has an Element child that is not child */
                for ($n=$parent->firstChild(); $n!==NULL; $n=$n->nextSibling()) {
                        if ($n->_nodeType === ELEMENT_NODE && $n !== $child) {
                                \domo\error("HierarchyRequestError");
                        }
                }
                /* #6b-2: DocumentType is following child. */
                for ($n=$child->nextSibling(); $n!==NULL; $n=$n->nextSibling()) {
                        if ($n->nodeType === DOCUMENT_TYPE_NODE) {
                                \domo\error("HierarchyRequestError");
                        }
                }
                break;
        case DOCUMENT_TYPE_NODE:
                /* #6c-1: parent has a DocumentType child */
                for ($n=$parent->firstChild(); $n!==NULL; $n=$n->nextSibling()) {
                        if ($n->_nodeType === DOCUMENT_TYPE_NODE) {
                                \domo\error("HierarchyRequestError");
                        }
                }
                /* #6c-2: an Element is preceding child */
                for ($n=$child->previousSibling(); $n!==NULL; $n=$n->previousSibling()) {
                        if ($n->_nodeType === ELEMENT_NODE) {
                                \domo\error("HierarchyRequestError");
                        }
                }
                break;
        }
}



/**
 * PORT NOTES
 *      The `serializeOne()` function used to live on the `Node.prototype`
 *      as a private method `Node#_serializeOne(child)`, however that requires
 *      a megamorphic property access `this._serializeOne` just to get to the
 *      method, and this is being done on lots of different `Node` subclasses,
 *      which puts a lot of pressure on V8's megamorphic stub cache. So by
 *      moving the helper off of the `Node.prototype` and into a separate
 *      function in this helper module, we get a monomorphic property access
 *      `NodeUtils.serializeOne` to get to the function and reduce pressure
 *      on the megamorphic stub cache.
 *      See https://github.com/fgnass/domino/pull/142 for more information.
 */
/* http://www.whatwg.org/specs/web-apps/current-work/multipage/the-end.html#serializing-html-fragments */
$hasRawContent = array(
        "STYLE" => true,
        "SCRIPT" => true,
        "XMP" => true,
        "IFRAME" => true,
        "NOEMBED" => true,
        "NOFRAMES" => true,
        "PLAINTEXT" => true
);

$emptyElements = array(
        "area" => true,
        "base" => true,
        "basefont" => true,
        "bgsound" => true,
        "br" => true,
        "col" => true,
        "embed" => true,
        "frame" => true,
        "hr" => true,
        "img" => true,
        "input" => true,
        "keygen" => true,
        "link" => true,
        "meta" => true,
        "param" => true,
        "source" => true,
        "track" => true,
        "wbr" => true
);

$extraNewLine = array(
        /* Removed in https://github.com/whatwg/html/issues/944 */
        /*
        "pre" => true,
        "textarea" => true,
        "listing" => true
        */
);

function _helper_escape($s)
{
        return str_replace(
                /* PORT: PHP7: \u{00a0} */
                /*
                 * NOTE: '&'=>'&amp;' must come first! Processing done LTR,
                 * so otherwise we will recursively replace the &'s.
                 */
                array("&","<",">","\u{00a0}"),
                array("&amp;", "&lt;", "&gt;", "&nbsp;"),
                $s
        );
}

function _helper_escapeAttr($s)
{
        return str_replace(
                array("&", "\"", "\u{00a0}"),
                array("&amp;", "&quot;", "&nbsp;"),
                $s
        );

        /* TODO: Is there still a fast path in PHP? (see NodeUtils.js) */
}

function _helper_attrname($a)
{
        $ns = $a->namespaceURI();

        if (!$ns) {
                return $a->localName();
        }

        if ($ns === NAMESPACE_XML) {
                return 'xml:'.$a->localName();
        }
        if ($ns === NAMESPACE_XLINK) {
                return 'xlink:'.$a->localName();
        }
        if ($ns === NAMESPACE_XMLNS) {
                if ($a->localName() === 'xmlns') {
                        return 'xmlns';
                } else {
                        return 'xmlns:' . $a->localName();
                }
        }

        return $a->name();
}

static function serialize_node($child, $parent)
{
        $s = "";

        switch ($child->_nodeType) {
        case ELEMENT_NODE: 
                $ns = $child->namespaceURI();
                $html = ($ns === NAMESPACE_HTML);

                if ($html || $ns === NAMESPACE_SVG || $ns === NAMESPACE_MATHML) {
                        $tagname = $child->localName();
                } else {
                        $tagname = $child->tagName();
                }

                $s += "<" + $tagname;

                foreach ($child->attributes) {
                        $s += " " + _helper_attrname($a);

                        /*
                         * PORT: TODO: Need to ensure this value is NULL
                         * rather than undefined?
                         */
                        if ($a->value() !== NULL) {
                                $s += '="' + _helper_escapeAttr($a->value()) + '"';
                        }
                }

                $s += '>';

                if (!($html && isset($emptyElements[$tagname]))) {
                        /* PORT: TODO: Check this serialize function */
                        $ss = serialize_node($child, NULL);
                        if ($html && isset($extraNewLine[$tagname]) && $ss[0]==='\n') {
                                $s += '\n';
                        }
                        /* Serialize children and add end tag for all others */
                        $s += $ss;
                        $s += '</' + $tagname + '>';
                }
                break;

        case TEXT_NODE:
        case CDATA_SECTION_NODE: 
                if ($parent->_nodeType === ELEMENT_NODE && $parent->namespaceURI() === NAMESPACE_HTML) {
                        $parenttag = $parent->tagName();
                } else {
                        $parenttag = '';
                }

                if ($hasRawContent[$parenttag] || ($parenttag==='NOSCRIPT' && $parent->ownerDocument()->_scripting_enabled)) {
                        $s += $child->data();
                } else {
                        $s += _helper_escape($child->data());
                }
                break;

        case COMMENT_NODE:
                $s += '<!--' + $child->data() + '-->';
                break;

        case PROCESSING_INSTRUCTION_NODE: 
                $s += '<?' + $child->target() + ' ' + $kid->data() + '?>';
                break;

        case DOCUMENT_TYPE_NODE:
                $s += '<!DOCTYPE ' + $child->name();

                if (false) {
                        // Latest HTML serialization spec omits the public/system ID
                        if ($child->_publicID) {
                                $s += ' PUBLIC "' + $child->_publicId + '"';
                        }

                        if ($child->_systemId) {
                                $s += ' "' + $child->_systemId + '"';
                        }
                }

                $s += '>';
                break;
        default:
                \domo\error("InvalidState");
        }

        return $s;
}

?>
