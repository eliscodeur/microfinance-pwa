"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["resources_js_Pages_Credits_Settings_jsx"],{

/***/ "./resources/js/Layouts/AdminLayout.jsx":
/*!**********************************************!*\
  !*** ./resources/js/Layouts/AdminLayout.jsx ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ AdminLayout)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");

function AdminLayout(_ref) {
  var children = _ref.children;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
    children: children
  });
}

/***/ }),

/***/ "./resources/js/Pages/Credits/Settings.jsx":
/*!*************************************************!*\
  !*** ./resources/js/Pages/Credits/Settings.jsx ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Settings)
/* harmony export */ });
/* harmony import */ var _inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @inertiajs/inertia-react */ "./node_modules/@inertiajs/inertia-react/dist/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _Layouts_AdminLayout_jsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../Layouts/AdminLayout.jsx */ "./resources/js/Layouts/AdminLayout.jsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "./node_modules/react/jsx-runtime.js");
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }




var TABS = [{
  key: "products",
  label: "Produits",
  icon: "bi-box-seam"
}, {
  key: "types",
  label: "Types",
  icon: "bi-diagram-3"
}, {
  key: "objects",
  label: "Motifs",
  icon: "bi-bullseye"
}];
var activeForm = function activeForm(form) {
  return form.setData("is_active", !form.data.is_active);
};
function FieldError(_ref) {
  var form = _ref.form,
    name = _ref.name;
  return form.errors[name] ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
    className: "text-danger small mt-1",
    children: form.errors[name]
  }) : null;
}
function Settings(_ref2) {
  var _ref2$types = _ref2.types,
    types = _ref2$types === void 0 ? [] : _ref2$types,
    _ref2$products = _ref2.products,
    products = _ref2$products === void 0 ? [] : _ref2$products,
    _ref2$objects = _ref2.objects,
    objects = _ref2$objects === void 0 ? [] : _ref2$objects;
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)("products"),
    _useState2 = _slicedToArray(_useState, 2),
    tab = _useState2[0],
    setTab = _useState2[1];
  var _useState3 = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(null),
    _useState4 = _slicedToArray(_useState3, 2),
    editing = _useState4[0],
    setEditing = _useState4[1];
  var typeForm = (0,_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__.useForm)({
    nom: "",
    code: "",
    is_active: true
  });
  var objectForm = (0,_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__.useForm)({
    nom: "",
    secteur_activite: "",
    is_active: true
  });
  var productForm = (0,_inertiajs_inertia_react__WEBPACK_IMPORTED_MODULE_0__.useForm)({
    credit_type_id: "",
    nom: "",
    code: "",
    type_carnet_requis: "compte",
    taux_interet_defaut: "",
    frais_dossier_defaut: "",
    duree_max_mois: 1,
    is_active: true,
    object_ids: []
  });
  var clearForm = function clearForm(kind) {
    setEditing(null);
    if (kind === "types") typeForm.reset();
    if (kind === "objects") objectForm.reset();
    if (kind === "products") productForm.reset();
  };
  var beginEdit = function beginEdit(kind, item) {
    setEditing({
      kind: kind,
      id: item.id
    });
    if (kind === "types") {
      typeForm.setData({
        nom: item.nom,
        code: item.code,
        is_active: Boolean(item.is_active)
      });
    } else if (kind === "objects") {
      objectForm.setData({
        nom: item.nom,
        secteur_activite: item.secteur_activite || "",
        is_active: Boolean(item.is_active)
      });
    } else {
      productForm.setData({
        credit_type_id: String(item.credit_type_id),
        nom: item.nom,
        code: item.code,
        type_carnet_requis: item.type_carnet_requis,
        taux_interet_defaut: item.taux_interet_defaut,
        frais_dossier_defaut: item.frais_dossier_defaut,
        duree_max_mois: item.duree_max_mois,
        is_active: Boolean(item.is_active),
        object_ids: (item.credit_objects || []).map(function (object) {
          return String(object.id);
        })
      });
    }
  };
  var submit = function submit(kind, form) {
    return function (event) {
      event.preventDefault();
      var isEditing = (editing === null || editing === void 0 ? void 0 : editing.kind) === kind;
      var url = isEditing ? "/admin/credit-settings/".concat(kind, "/").concat(editing.id) : "/admin/credit-settings/".concat(kind);
      var options = {
        preserveScroll: true,
        onSuccess: function onSuccess() {
          return clearForm(kind);
        }
      };
      if (isEditing) form.put(url, options);else form.post(url, options);
    };
  };
  var toggleObject = function toggleObject(id) {
    var selected = productForm.data.object_ids;
    productForm.setData("object_ids", selected.includes(String(id)) ? selected.filter(function (item) {
      return item !== String(id);
    }) : [].concat(_toConsumableArray(selected), [String(id)]));
  };
  var renderStatus = function renderStatus(item) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
      className: "badge ".concat(item.is_active ? "bg-success" : "bg-secondary"),
      children: item.is_active ? "Actif" : "Inactif"
    });
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_Layouts_AdminLayout_jsx__WEBPACK_IMPORTED_MODULE_2__["default"], {
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("main", {
      className: "container-fluid py-4",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
        className: "mb-4",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("h1", {
          className: "h3 fw-bold mb-1",
          children: "Param\xE9trage des cr\xE9dits"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("p", {
          className: "text-muted mb-0",
          children: "G\xE9rez les produits, types et motifs propos\xE9s lors d\u2019une demande de cr\xE9dit."
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("ul", {
        className: "nav nav-tabs mb-4",
        children: TABS.map(function (item) {
          return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("li", {
            className: "nav-item",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("button", {
              type: "button",
              className: "nav-link ".concat(tab === item.key ? "active" : ""),
              onClick: function onClick() {
                setTab(item.key);
                setEditing(null);
              },
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("i", {
                className: "bi ".concat(item.icon, " me-2")
              }), item.label]
            })
          }, item.key);
        })
      }), tab === "types" && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
        className: "row g-4",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
          className: "col-lg-4",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("form", {
            className: "card border-0 shadow-sm",
            onSubmit: submit("types", typeForm),
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
              className: "card-body",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("h2", {
                className: "h5 mb-3",
                children: (editing === null || editing === void 0 ? void 0 : editing.kind) === "types" ? "Modifier le type" : "Ajouter un type"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                className: "form-label",
                children: "Nom"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
                className: "form-control mb-1",
                value: typeForm.data.nom,
                onChange: function onChange(e) {
                  return typeForm.setData("nom", e.target.value);
                },
                required: true
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(FieldError, {
                form: typeForm,
                name: "nom"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                className: "form-label mt-3",
                children: "Code"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
                className: "form-control mb-1",
                value: typeForm.data.code,
                onChange: function onChange(e) {
                  return typeForm.setData("code", e.target.value);
                },
                required: true
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(FieldError, {
                form: typeForm,
                name: "code"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                className: "form-check mt-3",
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
                  id: "type-active",
                  className: "form-check-input",
                  type: "checkbox",
                  checked: typeForm.data.is_active,
                  onChange: function onChange() {
                    return activeForm(typeForm);
                  }
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                  className: "form-check-label",
                  htmlFor: "type-active",
                  children: "Type actif"
                })]
              })]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
              className: "card-footer bg-white d-flex gap-2",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
                className: "btn btn-primary",
                disabled: typeForm.processing,
                children: (editing === null || editing === void 0 ? void 0 : editing.kind) === "types" ? "Enregistrer" : "Ajouter"
              }), (editing === null || editing === void 0 ? void 0 : editing.kind) === "types" && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
                type: "button",
                className: "btn btn-outline-secondary",
                onClick: function onClick() {
                  return clearForm("types");
                },
                children: "Annuler"
              })]
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
          className: "col-lg-8",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
            className: "card border-0 shadow-sm",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
              className: "table-responsive",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("table", {
                className: "table align-middle mb-0",
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("thead", {
                  className: "table-light",
                  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tr", {
                    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                      children: "Nom"
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                      children: "Code"
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                      children: "Produits"
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                      children: "\xC9tat"
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {})]
                  })
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tbody", {
                  children: [types.map(function (item) {
                    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tr", {
                      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                        children: item.nom
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                        children: item.code
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                        children: item.products_count
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                        children: renderStatus(item)
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                        className: "text-end",
                        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
                          className: "btn btn-sm btn-outline-primary",
                          onClick: function onClick() {
                            return beginEdit("types", item);
                          },
                          children: "Modifier"
                        })
                      })]
                    }, item.id);
                  }), !types.length && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("tr", {
                    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      colSpan: "5",
                      className: "text-center text-muted py-4",
                      children: "Aucun type configur\xE9."
                    })
                  })]
                })]
              })
            })
          })
        })]
      }), tab === "objects" && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
        className: "row g-4",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
          className: "col-lg-4",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("form", {
            className: "card border-0 shadow-sm",
            onSubmit: submit("objects", objectForm),
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
              className: "card-body",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("h2", {
                className: "h5 mb-3",
                children: (editing === null || editing === void 0 ? void 0 : editing.kind) === "objects" ? "Modifier le motif" : "Ajouter un motif"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                className: "form-label",
                children: "Nom"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
                className: "form-control",
                value: objectForm.data.nom,
                onChange: function onChange(e) {
                  return objectForm.setData("nom", e.target.value);
                },
                required: true
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(FieldError, {
                form: objectForm,
                name: "nom"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                className: "form-label mt-3",
                children: "Secteur d\u2019activit\xE9"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
                className: "form-control",
                value: objectForm.data.secteur_activite,
                onChange: function onChange(e) {
                  return objectForm.setData("secteur_activite", e.target.value);
                }
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(FieldError, {
                form: objectForm,
                name: "secteur_activite"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                className: "form-check mt-3",
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
                  id: "object-active",
                  className: "form-check-input",
                  type: "checkbox",
                  checked: objectForm.data.is_active,
                  onChange: function onChange() {
                    return activeForm(objectForm);
                  }
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                  className: "form-check-label",
                  htmlFor: "object-active",
                  children: "Motif actif"
                })]
              })]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
              className: "card-footer bg-white d-flex gap-2",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
                className: "btn btn-primary",
                disabled: objectForm.processing,
                children: (editing === null || editing === void 0 ? void 0 : editing.kind) === "objects" ? "Enregistrer" : "Ajouter"
              }), (editing === null || editing === void 0 ? void 0 : editing.kind) === "objects" && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
                type: "button",
                className: "btn btn-outline-secondary",
                onClick: function onClick() {
                  return clearForm("objects");
                },
                children: "Annuler"
              })]
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
          className: "col-lg-8",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
            className: "card border-0 shadow-sm",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
              className: "table-responsive",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("table", {
                className: "table align-middle mb-0",
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("thead", {
                  className: "table-light",
                  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tr", {
                    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                      children: "Motif"
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                      children: "Secteur"
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                      children: "Produits"
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                      children: "\xC9tat"
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {})]
                  })
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tbody", {
                  children: [objects.map(function (item) {
                    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tr", {
                      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                        children: item.nom
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                        children: item.secteur_activite || "—"
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                        children: item.credit_products_count
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                        children: renderStatus(item)
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                        className: "text-end",
                        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
                          className: "btn btn-sm btn-outline-primary",
                          onClick: function onClick() {
                            return beginEdit("objects", item);
                          },
                          children: "Modifier"
                        })
                      })]
                    }, item.id);
                  }), !objects.length && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("tr", {
                    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      colSpan: "5",
                      className: "text-center text-muted py-4",
                      children: "Aucun motif configur\xE9."
                    })
                  })]
                })]
              })
            })
          })
        })]
      }), tab === "products" && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
        className: "row g-4",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
          className: "col-lg-5",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("form", {
            className: "card border-0 shadow-sm",
            onSubmit: submit("products", productForm),
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
              className: "card-body",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("h2", {
                className: "h5 mb-3",
                children: (editing === null || editing === void 0 ? void 0 : editing.kind) === "products" ? "Modifier le produit" : "Ajouter un produit"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                className: "row g-3",
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                  className: "col-md-8",
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                    className: "form-label",
                    children: "Nom"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
                    className: "form-control",
                    value: productForm.data.nom,
                    onChange: function onChange(e) {
                      return productForm.setData("nom", e.target.value);
                    },
                    required: true
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(FieldError, {
                    form: productForm,
                    name: "nom"
                  })]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                  className: "col-md-4",
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                    className: "form-label",
                    children: "Code"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
                    className: "form-control",
                    value: productForm.data.code,
                    onChange: function onChange(e) {
                      return productForm.setData("code", e.target.value);
                    },
                    required: true
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(FieldError, {
                    form: productForm,
                    name: "code"
                  })]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                  className: "col-md-6",
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                    className: "form-label",
                    children: "Type de cr\xE9dit"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("select", {
                    className: "form-select",
                    value: productForm.data.credit_type_id,
                    onChange: function onChange(e) {
                      return productForm.setData("credit_type_id", e.target.value);
                    },
                    required: true,
                    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("option", {
                      value: "",
                      children: "Choisir un type"
                    }), types.map(function (item) {
                      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("option", {
                        value: item.id,
                        disabled: !item.is_active && String(item.id) !== String(productForm.data.credit_type_id),
                        children: [item.nom, item.is_active ? "" : " (inactif)"]
                      }, item.id);
                    })]
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(FieldError, {
                    form: productForm,
                    name: "credit_type_id"
                  })]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                  className: "col-md-6",
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                    className: "form-label",
                    children: "Carnet requis"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("select", {
                    className: "form-select",
                    value: productForm.data.type_carnet_requis,
                    onChange: function onChange(e) {
                      return productForm.setData("type_carnet_requis", e.target.value);
                    },
                    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("option", {
                      value: "compte",
                      children: "Compte"
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("option", {
                      value: "tontine",
                      children: "Tontine"
                    })]
                  })]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                  className: "col-md-4",
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                    className: "form-label",
                    children: "Taux (%)"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
                    className: "form-control",
                    type: "number",
                    step: "0.01",
                    min: "0",
                    max: "100",
                    value: productForm.data.taux_interet_defaut,
                    onChange: function onChange(e) {
                      return productForm.setData("taux_interet_defaut", e.target.value);
                    },
                    required: true
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(FieldError, {
                    form: productForm,
                    name: "taux_interet_defaut"
                  })]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                  className: "col-md-4",
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                    className: "form-label",
                    children: "Frais de dossier"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
                    className: "form-control",
                    type: "number",
                    step: "1",
                    min: "0",
                    value: productForm.data.frais_dossier_defaut,
                    onChange: function onChange(e) {
                      return productForm.setData("frais_dossier_defaut", e.target.value);
                    },
                    required: true
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(FieldError, {
                    form: productForm,
                    name: "frais_dossier_defaut"
                  })]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                  className: "col-md-4",
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                    className: "form-label",
                    children: "Dur\xE9e max. (mois)"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
                    className: "form-control",
                    type: "number",
                    min: "1",
                    max: "120",
                    value: productForm.data.duree_max_mois,
                    onChange: function onChange(e) {
                      return productForm.setData("duree_max_mois", e.target.value);
                    },
                    required: true
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(FieldError, {
                    form: productForm,
                    name: "duree_max_mois"
                  })]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                  className: "col-12",
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                    className: "form-label",
                    children: "Motifs autoris\xE9s"
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                    className: "border rounded p-3",
                    style: {
                      maxHeight: 180,
                      overflowY: "auto"
                    },
                    children: [objects.map(function (item) {
                      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                        className: "form-check",
                        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
                          id: "object-".concat(item.id),
                          className: "form-check-input",
                          type: "checkbox",
                          checked: productForm.data.object_ids.includes(String(item.id)),
                          disabled: !item.is_active && !productForm.data.object_ids.includes(String(item.id)),
                          onChange: function onChange() {
                            return toggleObject(item.id);
                          }
                        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("label", {
                          className: "form-check-label",
                          htmlFor: "object-".concat(item.id),
                          children: [item.nom, item.is_active ? "" : " (inactif)"]
                        })]
                      }, item.id);
                    }), !objects.length && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
                      className: "text-muted small",
                      children: "Cr\xE9ez d\u2019abord un motif."
                    })]
                  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(FieldError, {
                    form: productForm,
                    name: "object_ids"
                  })]
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
                  className: "col-12",
                  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
                    className: "form-check",
                    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
                      id: "product-active",
                      className: "form-check-input",
                      type: "checkbox",
                      checked: productForm.data.is_active,
                      onChange: function onChange() {
                        return activeForm(productForm);
                      }
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("label", {
                      className: "form-check-label",
                      htmlFor: "product-active",
                      children: "Produit actif"
                    })]
                  })
                })]
              })]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
              className: "card-footer bg-white d-flex gap-2",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
                className: "btn btn-primary",
                disabled: productForm.processing || !types.length,
                children: (editing === null || editing === void 0 ? void 0 : editing.kind) === "products" ? "Enregistrer" : "Ajouter"
              }), (editing === null || editing === void 0 ? void 0 : editing.kind) === "products" && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
                type: "button",
                className: "btn btn-outline-secondary",
                onClick: function onClick() {
                  return clearForm("products");
                },
                children: "Annuler"
              })]
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
          className: "col-lg-7",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
            className: "card border-0 shadow-sm",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
              className: "table-responsive",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("table", {
                className: "table align-middle mb-0",
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("thead", {
                  className: "table-light",
                  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tr", {
                    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                      children: "Produit"
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                      children: "Type"
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                      children: "Support"
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                      children: "Taux"
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {
                      children: "\xC9tat"
                    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("th", {})]
                  })
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tbody", {
                  children: [products.map(function (item) {
                    var _item$type;
                    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("tr", {
                      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("td", {
                        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
                          className: "fw-semibold",
                          children: item.nom
                        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
                          className: "small text-muted",
                          children: item.code
                        })]
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                        children: ((_item$type = item.type) === null || _item$type === void 0 ? void 0 : _item$type.nom) || "—"
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                        children: item.type_carnet_requis
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("td", {
                        children: [item.taux_interet_defaut, "%"]
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                        children: renderStatus(item)
                      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                        className: "text-end",
                        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
                          className: "btn btn-sm btn-outline-primary",
                          onClick: function onClick() {
                            return beginEdit("products", item);
                          },
                          children: "Modifier"
                        })
                      })]
                    }, item.id);
                  }), !products.length && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("tr", {
                    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("td", {
                      colSpan: "6",
                      className: "text-center text-muted py-4",
                      children: "Aucun produit configur\xE9."
                    })
                  })]
                })]
              })
            })
          })
        })]
      })]
    })
  });
}

/***/ })

}]);