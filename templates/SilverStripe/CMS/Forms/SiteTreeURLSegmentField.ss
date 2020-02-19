<div class="preview-holder input-group">
    <% if $URL %>
    <a class="URL-link" href="$URL" target="_blank">
        $URL
    </a>
    <% else %>
        <input $AttributesHTML readonly>
    <% end_if %>
    <% if not $IsReadonly %>
        <% if not $URL %>
        <div class="input-group-append">
        <% end_if %>
        <button role="button" type="button" class="btn btn-outline-secondary btn-sm edit">
            <%t SilverStripe\CMS\Forms\SiteTreeURLSegmentField.Edit 'Edit' %>
        </button>
        <% if not $URL %>
        </div>
        <% end_if %>
    <% end_if %>
</div>

<div class="edit-holder">
    <div class="input-group">
        <input $AttributesHTML />
        <div class="input-group-append">
            <button role="button" data-icon="accept" type="button" class="btn btn-primary update">
                <%t SilverStripe\CMS\Forms\SiteTreeURLSegmentField.OK 'OK' %>
            </button>
        </div>
        <div class="input-group-append">
            <button role="button" data-icon="cancel" type="button" class="btn btn-outline-secondary btn-sm input-group-append cancel">
                <%t SilverStripe\CMS\Forms\SiteTreeURLSegmentField.Cancel 'Cancel' %>
            </button>
        </div>
    </div>
    <% if $HelpText %><p class="form__field-description">$HelpText</p><% end_if %>
</div>
