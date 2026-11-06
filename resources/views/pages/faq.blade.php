<x-layouts.app title="FAQ" description="Jawaban atas pertanyaan yang paling sering diajukan seputar UC Color Run 2027.">
    <div class="page-container page-section">
        <x-panel>
            <x-panel-heading title="Frequently Asked Questions" description="Temukan jawaban atas pertanyaan yang paling sering diajukan seputar registrasi, pembayaran, race pack, dan hari lomba." />

            <div class="mt-6 grid gap-5 md:mt-8 md:grid-cols-[minmax(0,15rem)_minmax(0,1fr)] md:gap-8" data-tabs>
                <div
                    role="tablist"
                    aria-label="Kategori FAQ"
                    class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-1 scrollbar-none sm:-mx-8 sm:px-8 md:mx-0 md:flex-col md:overflow-visible md:px-0 md:pb-0"
                >
                    @foreach ($faqGroups as $group)
                        <button
                            type="button"
                            role="tab"
                            id="faq-tab-{{ $loop->index }}"
                            aria-controls="faq-panel-{{ $loop->index }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            tabindex="{{ $loop->first ? 0 : -1 }}"
                            class="flex min-h-11 shrink-0 items-center justify-between gap-3 rounded-xl bg-background px-4 text-left text-body-sm font-medium whitespace-nowrap text-muted transition-colors hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary aria-selected:bg-primary aria-selected:text-white md:w-full md:whitespace-normal"
                        >
                            {{ $group['category'] }}
                            <x-icon name="chevron-right" class="hidden size-4 md:block" />
                        </button>
                    @endforeach
                </div>

                @foreach ($faqGroups as $group)
                    @php($groupIndex = $loop->index)
                    <div
                        role="tabpanel"
                        id="faq-panel-{{ $groupIndex }}"
                        aria-labelledby="faq-tab-{{ $groupIndex }}"
                        @unless ($loop->first) hidden @endunless
                    >
                        <div class="flex flex-col gap-3" data-accordion>
                            @foreach ($group['items'] as $item)
                                @php($isOpen = $groupIndex === 0 && $loop->first)
                                <div class="rounded-2xl border border-border bg-surface transition-colors data-open:border-primary/30 data-open:bg-primary/5" data-accordion-item @if ($isOpen) data-open @endif>
                                    <h3>
                                        <button
                                            type="button"
                                            id="faq-question-{{ $groupIndex }}-{{ $loop->index }}"
                                            class="group flex w-full items-center justify-between gap-4 rounded-2xl px-4 py-4 text-left text-body font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary sm:px-5"
                                            aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                            aria-controls="faq-answer-{{ $groupIndex }}-{{ $loop->index }}"
                                            data-accordion-trigger
                                        >
                                            {{ $item['question'] }}
                                            <span class="grid size-7 shrink-0 place-items-center rounded-full border border-border bg-surface text-foreground transition group-aria-expanded:rotate-45 group-aria-expanded:border-primary group-aria-expanded:text-primary">
                                                <x-icon name="plus" class="size-4" />
                                            </span>
                                        </button>
                                    </h3>
                                    <div
                                        id="faq-answer-{{ $groupIndex }}-{{ $loop->index }}"
                                        role="region"
                                        aria-labelledby="faq-question-{{ $groupIndex }}-{{ $loop->index }}"
                                        class="px-4 pb-5 text-body-sm text-muted sm:px-5"
                                        @unless ($isOpen) hidden @endunless
                                    >
                                        {{ $item['answer'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mt-8 border-t border-border pt-6 text-center text-body-sm text-muted">
                Tidak menemukan jawabanmu?
                <a href="{{ route('contact') }}" class="font-semibold text-primary hover:underline">Hubungi panitia</a>
            </p>
        </x-panel>
    </div>
</x-layouts.app>
