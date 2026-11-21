<native:column class="w-full flex-1 bg-theme-background">
    <firstlight:list separator class="flex-1 bg-theme-background">
        @foreach ($rows as $row)
            @if ($row['icon'] !== '')
                <firstlight:list-item
                    :headline="$row['label']"
                    :leading-icon="$row['icon']"
                    :trailing-text="$row['badge']"
                    navigates
                    @press="open('{{ $row['id'] }}')"
                />
            @else
                <firstlight:list-item
                    :headline="$row['label']"
                    :trailing-text="$row['badge']"
                    navigates
                    @press="open('{{ $row['id'] }}')"
                />
            @endif
        @endforeach
    </firstlight:list>
</native:column>
