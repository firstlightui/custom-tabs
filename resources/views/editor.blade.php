<native:scroll-view class="w-full flex-1 bg-theme-background">
    <native:column class="w-full p-4 pb-8 gap-6">
        <firstlight:reorder-list
            :items="$items"
            :sections="$sections"
            :value="$arrangement"
            :helper="$helper"
            :a11y-label="__('custom-tabs::tabs.title')"
            @change="move"
        />

        <firstlight:list-item
            :headline="__('custom-tabs::tabs.reset')"
            @press="requestReset"
        />

        <firstlight:confirmation-dialog
            :visible="$confirmingReset"
            :title="__('custom-tabs::tabs.reset_title')"
            :message="__('custom-tabs::tabs.reset_message')"
            :confirm-label="__('custom-tabs::tabs.reset')"
            @press="resetTabs"
            @dismiss="cancelReset"
        />
    </native:column>
</native:scroll-view>
