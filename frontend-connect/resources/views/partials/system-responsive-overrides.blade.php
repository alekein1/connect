/* Shared responsive layer for admin and superadmin modules */
.content .page-wrap,
.content .panel-card,
.content .hero-card,
.content .detail-card,
.content .box,
.content .table-wrap,
.content .modal-card,
.content .modal-pro,
.content .stats-grid,
.content .filter-grid,
.content .detail-grid,
.content .two-col,
.content .form-grid,
.content .mini-grid,
.content .chart-row,
.content .pagination-bar,
.content .meta-chips,
.content .actions,
.content .actions-inline,
.content .detail-actions,
.content .modal-actions,
.content .toolbar,
.content .header-actions{
    min-width:0;
}

.content .panel-card,
.content .hero-card,
.content .detail-card,
.content .modal-card{
    max-width:100%;
}

.content .field,
.content .field-inline,
.content .select-pro-wrapper{
    min-width:0;
}

.content .field input,
.content .field select,
.content .field textarea,
.content .field-inline input,
.content .field-inline select,
.content .field-inline textarea,
.content .input-pro,
.content .select-pro,
.content .search-products,
.content .modal-textarea{
    max-width:100%;
}

.content .table-wrap{
    max-width:100%;
    overflow-x:auto;
    overflow-y:hidden;
}

.content .table-wrap table{
    border-collapse:collapse;
}

.content .table-empty,
.content .muted,
.content .mono,
.content .hero-copy,
.content .panel-subtitle,
.content .detail-subtitle{
    overflow-wrap:anywhere;
}

@media (max-width: 1024px){
    .content .stats-grid,
    .content .filter-grid,
    .content .detail-grid,
    .content .two-col,
    .content .form-grid,
    .content .mini-grid,
    .content .chart-row{
        grid-template-columns:1fr !important;
    }

    .content .toolbar,
    .content .header-actions,
    .content .filter-actions,
    .content .actions,
    .content .actions-inline,
    .content .modal-actions,
    .content .detail-actions,
    .content .pagination-bar,
    .content .meta-chips{
        flex-direction:column;
        align-items:stretch !important;
    }

    .content .toolbar > *,
    .content .header-actions > *,
    .content .filter-actions > *,
    .content .actions > *,
    .content .actions-inline > *,
    .content .modal-actions > *,
    .content .detail-actions > *,
    .content .pagination-bar > *{
        width:100%;
        max-width:100%;
    }

    .content .field-inline,
    .content .field,
    .content .select-pro-wrapper{
        width:100%;
    }

    .content .input-pro,
    .content .select-pro,
    .content .search-products,
    .content .field input,
    .content .field select,
    .content .field textarea,
    .content .field-inline input,
    .content .field-inline select,
    .content .field-inline textarea,
    .content .modal-textarea{
        width:100%;
        min-width:0 !important;
    }

    .content .btn-primary-pro,
    .content .btn-secondary-pro,
    .content .btn-danger-pro,
    .content .btn-action,
    .content .action-btn,
    .content .modal-btn{
        width:100%;
        justify-content:center;
    }

    .content .panel-title,
    .content .detail-title,
    .content .hero-title{
        font-size:clamp(20px, 5vw, 28px) !important;
    }

    .content .table-locales thead th,
    .content .table-pro thead th,
    .content .table-creditos thead th,
    .content .table-rides thead th,
    .content .table-report thead th{
        padding:13px 14px !important;
        font-size:11px !important;
    }

    .content .table-locales tbody td,
    .content .table-pro tbody td,
    .content .table-creditos tbody td,
    .content .table-rides tbody td,
    .content .table-report tbody td{
        padding:13px 14px !important;
    }

    .content .modal-pro{
        padding:14px !important;
    }

    .content .modal-card{
        width:min(100%, 560px) !important;
        max-height:92vh !important;
    }
}

@media (max-width: 640px){
    .content .panel-card,
    .content .hero-card,
    .content .detail-card,
    .content .box,
    .content .modal-card{
        border-radius:16px !important;
    }

    .content .badge,
    .content .badge-state{
        white-space:normal;
        text-align:center;
    }

    .content .kpi strong,
    .content .card p,
    .content .mini-stat strong{
        font-size:24px !important;
    }
}
