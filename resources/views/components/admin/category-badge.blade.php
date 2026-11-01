@props(['category'])

<span {{ $attributes->class('inline-flex items-center whitespace-nowrap rounded-full bg-primary/8 px-2.5 py-1 text-caption font-semibold text-primary') }}>{{ $category->label() }}</span>
