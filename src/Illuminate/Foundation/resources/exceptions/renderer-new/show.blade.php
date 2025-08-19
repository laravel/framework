@use('Illuminate\Foundation\Exceptions\Renderer\Renderer')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    />

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" type="image/svg+xml"
          href="data:image/svg+xml,%3Csvg viewBox='0 -.11376601 49.74245785 51.31690859' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='m49.626 11.564a.809.809 0 0 1 .028.209v10.972a.8.8 0 0 1 -.402.694l-9.209 5.302v10.509c0 .286-.152.55-.4.694l-19.223 11.066c-.044.025-.092.041-.14.058-.018.006-.035.017-.054.022a.805.805 0 0 1 -.41 0c-.022-.006-.042-.018-.063-.026-.044-.016-.09-.03-.132-.054l-19.219-11.066a.801.801 0 0 1 -.402-.694v-32.916c0-.072.01-.142.028-.21.006-.023.02-.044.028-.067.015-.042.029-.085.051-.124.015-.026.037-.047.055-.071.023-.032.044-.065.071-.093.023-.023.053-.04.079-.06.029-.024.055-.05.088-.069h.001l9.61-5.533a.802.802 0 0 1 .8 0l9.61 5.533h.002c.032.02.059.045.088.068.026.02.055.038.078.06.028.029.048.062.072.094.017.024.04.045.054.071.023.04.036.082.052.124.008.023.022.044.028.068a.809.809 0 0 1 .028.209v20.559l8.008-4.611v-10.51c0-.07.01-.141.028-.208.007-.024.02-.045.028-.068.016-.042.03-.085.052-.124.015-.026.037-.047.054-.071.024-.032.044-.065.072-.093.023-.023.052-.04.078-.06.03-.024.056-.05.088-.069h.001l9.611-5.533a.801.801 0 0 1 .8 0l9.61 5.533c.034.02.06.045.09.068.025.02.054.038.077.06.028.029.048.062.072.094.018.024.04.045.054.071.023.039.036.082.052.124.009.023.022.044.028.068zm-1.574 10.718v-9.124l-3.363 1.936-4.646 2.675v9.124l8.01-4.611zm-9.61 16.505v-9.13l-4.57 2.61-13.05 7.448v9.216zm-36.84-31.068v31.068l17.618 10.143v-9.214l-9.204-5.209-.003-.002-.004-.002c-.031-.018-.057-.044-.086-.066-.025-.02-.054-.036-.076-.058l-.002-.003c-.026-.025-.044-.056-.066-.084-.02-.027-.044-.05-.06-.078l-.001-.003c-.018-.03-.029-.066-.042-.1-.013-.03-.03-.058-.038-.09v-.001c-.01-.038-.012-.078-.016-.117-.004-.03-.012-.06-.012-.09v-21.483l-4.645-2.676-3.363-1.934zm8.81-5.994-8.007 4.609 8.005 4.609 8.006-4.61-8.006-4.608zm4.164 28.764 4.645-2.674v-20.096l-3.363 1.936-4.646 2.675v20.096zm24.667-23.325-8.006 4.609 8.006 4.609 8.005-4.61zm-.801 10.605-4.646-2.675-3.363-1.936v9.124l4.645 2.674 3.364 1.937zm-18.422 20.561 11.743-6.704 5.87-3.35-8-4.606-9.211 5.303-8.395 4.833z' fill='%23ff2d20'/%3E%3C/svg%3E" />

    {!! Renderer::css() !!}
</head>
<body class="bg-neutral-900 font-sans antialiased overflow-x-hidden text-white">
    <div class="relative min-h-screen bg-neutral-900">
        <!-- Topbar -->
        <div class="flex items-center justify-center w-full relative">
            <div class="box-border content-stretch flex flex-row items-center justify-between px-6 py-3 relative shrink-0 max-w-7xl w-full">
                <div aria-hidden="true" class="absolute border-[0px_1px] border-[rgba(255,255,255,0.09)] border-dashed bottom-0 left-[-0.5px] pointer-events-none right-[-0.5px] top-0"></div>
                <div class="box-border content-stretch flex flex-row gap-2 items-center justify-start p-0 relative shrink-0">
                    <div class="overflow-clip relative shrink-0 w-[18px] h-[18px]">
                        <svg class="w-full h-full text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.178 2.625-1.516 2.625H3.72c-1.337 0-2.19-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="font-['Inter:Medium',_sans-serif] font-medium leading-[0] not-italic relative shrink-0 text-[#ffffff] text-[14px] text-left text-nowrap tracking-[-0.28px]">
                        <p class="adjustLetterSpacing block leading-[20px] whitespace-pre">{{ $exception->title() }}</p>
                    </div>
                </div>
                <div class="box-border content-stretch flex flex-row gap-4 items-center justify-start p-0 relative shrink-0">
                    <div class="bg-[rgba(255,255,255,0.02)] box-border content-stretch flex flex-row h-8 items-center justify-center pl-1 pr-4 py-4 relative rounded-md shrink-0">
                        <div aria-hidden="true" class="absolute border border-neutral-800 border-solid inset-0 pointer-events-none rounded-md"></div>
                        <div class="box-border content-stretch flex flex-row gap-2 h-4 items-center justify-start mr-[-4px] pl-2 pr-0 py-0 relative shrink-0">
                            <div class="overflow-clip relative shrink-0 w-3 h-3">
                                <svg class="w-full h-full text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="box-border content-stretch flex flex-row gap-2 items-center justify-center mr-[-4px] px-3 py-0 relative shrink-0">
                            <div class="font-['Inter:Regular',_sans-serif] font-normal leading-[0] not-italic relative shrink-0 text-[14px] text-center text-neutral-400 text-nowrap tracking-[-0.28px]">
                                <p class="adjustLetterSpacing block leading-[20px] whitespace-pre">Enable advanced monitoring</p>
                            </div>
                        </div>
                        <div class="mr-[-4px] overflow-clip relative shrink-0 w-2 h-2">
                            <svg class="w-full h-full text-neutral-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Floating Nightwatch Installation Panel -->
                <div class="absolute backdrop-blur-[6px] backdrop-filter bg-neutral-800 box-border content-stretch flex flex-col gap-6 items-start justify-start right-[24px] p-[24px] rounded-lg top-[49px] w-[478px] z-50">
                    <div aria-hidden="true" class="absolute border border-[rgba(255,255,255,0.01)] border-solid inset-[-0.5px] pointer-events-none rounded-[8.5px] shadow-[0px_303px_85px_0px_rgba(0,0,0,0),0px_194px_77px_0px_rgba(0,0,0,0.04),0px_109px_65px_0px_rgba(0,0,0,0.13),0px_48px_48px_0px_rgba(0,0,0,0.22),0px_12px_27px_0px_rgba(0,0,0,0.25)]"></div>
                    <div class="box-border content-stretch flex flex-col gap-4 items-start justify-start p-0 relative shrink-0 w-full">
                        <div class="box-border content-stretch flex flex-row gap-[9px] items-center justify-start p-0 relative shrink-0">
                            <div class="font-['Inter:Semi_Bold',_sans-serif] font-semibold leading-[0] not-italic relative shrink-0 text-[#ffffff] text-[18px] text-left text-nowrap tracking-[-0.36px]">
                                <p class="adjustLetterSpacing block leading-[28px] whitespace-pre">Install Nightwatch Local</p>
                            </div>
                        </div>
                        <div class="font-['Inter:Light',_sans-serif] font-light leading-[0] min-w-full not-italic relative shrink-0 text-[14px] text-left text-neutral-300 tracking-[-0.28px]" style="width: min-content">
                            <p class="block leading-[1.5]">Install Nightwatch locally, with no account required. Capture logs, timelines, traces, and more to better debug local exceptions.</p>
                        </div>
                    </div>
                    <div class="bg-[rgba(255,255,255,0.03)] box-border content-stretch flex flex-row h-10 items-center justify-between pl-4 pr-2 py-1 relative rounded-lg shrink-0 w-full">
                        <div aria-hidden="true" class="absolute border border-[rgba(255,255,255,0.06)] border-solid inset-0 pointer-events-none rounded-lg"></div>
                        <div class="flex flex-col font-['CommitMonoLaravel:400Regular',_sans-serif] justify-center leading-[0] not-italic relative shrink-0 text-[#f6c177] text-[12px] text-left text-nowrap tracking-[-0.24px]">
                            <p class="adjustLetterSpacing block leading-none whitespace-pre">composer require laravel/nightwatch</p>
                        </div>
                        <div class="bg-[rgba(255,255,255,0.1)] box-border content-stretch flex flex-row items-center justify-center pl-0 pr-2 py-4 relative rounded-md shrink-0 w-6 h-6">
                            <div aria-hidden="true" class="absolute border border-[rgba(255,255,255,0.01)] border-solid inset-0 pointer-events-none rounded-md"></div>
                            <div class="mr-[-8px] overflow-clip relative shrink-0 w-3 h-3">
                                <svg class="w-full h-full text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-laravel-exceptions-renderer-new::separator />

        <!-- Header Section -->
        <div class="w-full max-w-7xl mx-auto flex flex-col gap-8 px-14 py-16 border-x border-white/[0.09] border-dashed">
            <div class="flex flex-col gap-6">
                <div class="flex items-center gap-[9px]">
                    <h1 class="text-3xl font-semibold text-white tracking-[-0.6px] leading-[36px]">{{ $exception->class() }}</h1>
                </div>
                <p class="text-xl font-light text-neutral-300 tracking-[-0.4px] leading-[30px] min-w-full">
                    {{ $exception->message() }}
                </p>
            </div>

            <!-- Metadata Section -->
            <div class="flex items-start gap-2">
                <div class="bg-white/[0.03] border border-white/[0.01] rounded-md shadow-sm flex items-center gap-0.5">
                    <div class="flex items-center gap-1.5 h-6 px-[6px] font-mono text-[13px] tracking-[-0.26px]">
                        <span class="text-neutral-500">LARAVEL</span>
                        <span class="text-neutral-300">{{ app()->version() }}</span>
                    </div>
                    <div class="w-0 h-[22px] flex items-center justify-center">
                        <div class="rotate-90 w-[22px] h-0 border-b border-white/[0.04]"></div>
                    </div>
                    <div class="flex items-center gap-1.5 h-6 px-[6px] font-mono text-[13px] tracking-[-0.26px]">
                        <span class="text-neutral-500">PHP</span>
                        <span class="text-neutral-300">{{ PHP_VERSION }}</span>
                    </div>
                </div>
                <div class="bg-rose-600 border border-rose-500 rounded-md h-6 flex items-center gap-1.5 px-[6px] min-w-6">
                    <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.178 2.625-1.516 2.625H3.72c-1.337 0-2.19-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-[13px] font-mono text-white tracking-[-0.26px]">UNHANDLED</span>
                </div>
                <div class="bg-rose-950 border border-rose-900 rounded-md h-6 flex items-center gap-1.5 px-[6px] min-w-6">
                    <span class="text-[13px] font-mono text-rose-100 tracking-[-0.26px]">CODE {{ $exception->code() }}</span>
                </div>
            </div>
        </div>

        <x-laravel-exceptions-renderer-new::separator />

        <div class="w-full max-w-7xl mx-auto border-x border-white/[0.09] border-dashed">
            <!-- Request Information Section -->
            <div class="flex flex-col items-center justify-start w-full relative">
                <!-- URL Panel -->
                <div class="w-full mx-auto px-14 relative">
                    <div class="backdrop-blur-[6px] bg-white/[0.04] border border-white/[0.01] rounded-lg flex items-center justify-between p-2 relative mt-[23px] w-full mx-auto">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-600 rounded h-[25px] flex items-center justify-center px-2">
                                <span class="text-[13px] font-mono text-white tracking-[-0.26px]">{{ $exception->request()->method() }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="opacity-60 w-3 h-3">
                                    <svg class="w-full h-full text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.56-.5-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.56.5.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.498-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-sm font-light text-white tracking-[-0.28px]">
                                {{ $exception->request()->fullUrl() }}
                            </div>
                        </div>
                        <div class="bg-white/[0.05] rounded-md w-6 h-6 flex items-center justify-center">
                            <svg class="w-3 h-3 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exception Overview Section -->
            <div class="flex flex-col gap-12 items-center justify-start pt-5 px-14 pb-0 w-full">
                <div class="flex flex-col w-full">
                    <div class="flex gap-6 items-start justify-start pb-3 pt-0 px-0 w-full">
                        <h2 class="text-lg font-semibold text-white tracking-[-0.36px] leading-[28px]">Overview</h2>
                    </div>
                    <div class="flex flex-col gap-5 w-full">
                        <div class="flex flex-col w-full">
                            <!-- Date Row -->
                            <div class="flex items-center gap-2 h-10 px-0 py-[18px] w-full">
                                <div class="text-sm font-mono text-neutral-400 tracking-[-0.28px] uppercase">DATE</div>
                                <div class="flex-1 h-3 border-b-2 border-dotted border-white/20"></div>
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-mono text-white tracking-[-0.28px]">
                                            {{ now()->format('Y/m/d H:i:s.v') }} <span class="text-neutral-500">UTC</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Status Code Row -->
                            <div class="flex items-center gap-2 h-10 px-0 py-[18px] w-full">
                                <div class="text-sm font-mono text-neutral-400 tracking-[-0.28px] uppercase">STATUS CODE</div>
                                <div class="flex-1 h-3 border-b-2 border-dotted border-white/20"></div>
                                <div class="flex items-center gap-4">
                                    <div class="bg-rose-500 border border-rose-500 rounded-md h-6 flex items-center gap-1.5 px-[6px] min-w-6">
                                        <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.178 2.625-1.516 2.625H3.72c-1.337 0-2.19-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-[13px] font-mono text-neutral-300 tracking-[-0.26px]">500</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Method Row -->
                            <div class="flex items-center gap-2 h-10 px-0 py-[18px] w-full">
                                <div class="text-sm font-mono text-neutral-400 tracking-[-0.28px] uppercase">METHOD</div>
                                <div class="flex-1 h-3 border-b-2 border-dotted border-white/20"></div>
                                <div class="flex items-center gap-4">
                                    <div class="bg-rose-500 border border-emerald-800 rounded-md h-6 flex items-center gap-1.5 px-[6px] min-w-6">
                                        <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.56-.5-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.56.5.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.498-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-[13px] font-mono text-neutral-300 tracking-[-0.26px]">{{ $exception->request()->method() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exception Trace Section -->
            <div class="flex flex-col gap-4 w-full px-14 ">
                <div class="bg-white/[0.01] border border-neutral-800 rounded-xl w-full">
                    <div class="flex flex-col overflow-hidden w-full">
                        <!-- Section Header -->
                        <div class="flex items-center justify-between h-[50px] pb-2 pt-[18px] px-[18px] w-full">
                            <div class="flex items-center gap-2.5 flex-1 min-w-12">
                                <div class="flex items-center gap-2.5">
                                    <div class="bg-neutral-800 rounded-md w-6 h-6 flex items-center justify-center p-1">
                                        <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.178 2.625-1.516 2.625H3.72c-1.337 0-2.19-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-semibold text-white tracking-[-0.32px] leading-[24px]">Exception trace</h3>
                                </div>
                            </div>
                        </div>

                        <!-- Stack Trace Content -->
                        <div class="flex flex-col gap-1 p-[10px] w-full">
                            <!-- Vendor Frames Toggle -->
                            <div class="bg-white/[0.01] opacity-90 rounded-lg h-11 w-full">
                                <div class="flex items-center justify-between overflow-hidden pl-4 pr-2.5 py-0 h-full w-full">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <svg class="w-3 h-3 text-neutral-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                        </svg>
                                        <span class="text-[13px] font-mono text-neutral-400 tracking-[-0.26px]">53 vendor frames</span>
                                    </div>
                                    <div class="bg-white/[0.03] rounded-md w-6 h-6 flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Main Exception Frame -->
                            <div class="bg-neutral-800 opacity-90 rounded-lg w-full">
                                <div class="flex flex-col overflow-hidden w-full">
                                    <!-- Frame Header -->
                                    <div class="bg-white/[0.04] flex items-center justify-between h-11 pl-4 pr-2.5 py-2.5 w-full border-b border-white/[0]">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="text-[13px] font-mono text-white tracking-[-0.26px]">App\Services\FlightService</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs font-mono text-neutral-300 tracking-[-0.24px]">
                                                resources/views/dashboard/partials/total-requests.blade.php<span class="text-neutral-500">:32</span>
                                            </span>
                                            <div class="bg-white/[0.05] rounded-md w-6 h-6 flex items-center justify-center">
                                                <svg class="w-2 h-[11px] text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Function Signature -->
                                    <div class="bg-white/[0.02] flex items-center justify-between h-8 pl-[18px] pr-3 py-2.5 w-full">
                                        <code class="text-[13px] font-mono text-[#62e884] tracking-[-0.26px] leading-none">
                                            <span class="text-[#dcdcaa]">createFlight</span><span class="text-[#f8f8f2]">(</span><span class="text-[#9cdcfe]">$id</span><span class="text-[#f8f8f2]">,</span><span class="text-[#50fa7b]"> </span><span class="text-[#9cdcfe]">$name</span><span class="text-[#f8f8f2]">,</span><span class="text-[#50fa7b]"> </span><span class="text-[#9cdcfe]">$origin</span><span class="text-[#f8f8f2]">,</span><span class="text-[#50fa7b]"> </span><span class="text-[#9cdcfe]">$destination</span><span class="text-[#f8f8f2]">,</span><span class="text-[#50fa7b]"> </span><span class="text-[#9cdcfe]">$departure_time</span><span class="text-[#f8f8f2]">,</span><span class="text-[#50fa7b]"> </span><span class="text-[#9cdcfe]">$arrival_time</span><span class="text-[#f8f8f2]">)</span>
                                        </code>
                                    </div>

                                    <!-- Code Block -->
                                    <div class="flex flex-col gap-2 items-center justify-center px-4 py-2 w-full relative">
                                        <!-- Background Lines -->
                                        <div class="absolute flex flex-col h-[278px] left-0 top-0 w-full">
                                            <div class="flex-1 bg-white/[0.04] opacity-0 w-full"></div>
                                            <div class="flex-1 bg-white/[0.02] w-full"></div>
                                            <div class="flex-1 bg-white/[0.04] opacity-0 w-full"></div>
                                            <div class="flex-1 bg-white/[0.04] opacity-0 w-full"></div>
                                            <div class="flex-1 bg-rose-900 w-full"></div>
                                            <div class="flex-1 bg-white/[0.02] w-full"></div>
                                            <div class="flex-1 bg-white/[0.04] opacity-0 w-full"></div>
                                            <div class="flex-1 bg-white/[0.02] w-full"></div>
                                            <div class="flex-1 bg-white/[0.04] opacity-0 w-full"></div>
                                            <div class="flex-1 bg-white/[0.02] w-full"></div>
                                        </div>

                                        <!-- Code Content -->
                                        <div class="flex gap-6 items-start justify-start font-mono w-full relative z-10">
                                            <div class="text-sm text-[rgba(225,228,232,0.3)] text-right tracking-[-0.28px] leading-[2] whitespace-pre">
29
30
31
32
33
34
35
36
37
38</div>
                                            <div class="text-[13px] text-[#89ddff] tracking-[-0.26px] w-[875px] leading-[2.16] whitespace-pre-wrap">
<span class="text-neutral-300">-></span><span class="text-[#dcdcaa]">when</span><span class="text-neutral-300">(</span><span class="text-[#dcdcaa]">in_array</span><span class="text-neutral-300">(</span><span class="text-[#9cdcfe]">$type</span><span class="text-neutral-300">, [</span>
<span class="text-neutral-300">        </span><span class="text-[#ce9178]">'exception'</span><span class="text-neutral-300">, </span><span class="text-[#ce9178]">'alert'</span><span class="text-neutral-300">,</span>
<span class="text-neutral-300">    ]), </span><span class="text-[#569cd6]">fn</span><span class="text-neutral-300"> (</span><span class="text-[#9cdcfe]">$query</span><span class="text-neutral-300">) => </span><span class="text-[#9cdcfe]">$query</span><span class="text-neutral-300">-></span><span class="text-[#dcdcaa]">where</span><span class="text-neutral-300">(</span><span class="text-[#ce9178]">'type'</span><span class="text-neutral-300">, </span><span class="text-[#9cdcfe]">$type</span><span class="text-neutral-300">))</span>
<span class="text-neutral-300">    -></span><span class="text-[#dcdcaa]">select</span><span class="text-neutral-300">(</span><span class="text-[#ce9178]">'ids'</span><span class="text-neutral-300">, </span><span class="text-[#ce9178]">'ref'</span><span class="text-neutral-300">, </span><span class="text-[#ce9178]">'type'</span><span class="text-neutral-300">, </span><span class="text-[#ce9178]">'issuable_type'</span><span class="text-neutral-300">, </span><span class="text-[#ce9178]">'priority'</span><span class="text-neutral-300">, </span><span class="text-[#ce9178]">'title'</span><span class="text-neutral-300">, </span><span class="text-[#ce9178]">'user_id'</span><span class="text-neutral-300">, </span><span class="text-[#ce9178]">'first_seen_at'</span><span class="text-neutral-300">, </span><span class="text-[#ce9178]">'last_seen_at'</span><span class="text-neutral-300">)</span>
<span class="text-neutral-300">    -></span><span class="text-[#dcdcaa]">orderBy</span><span class="text-neutral-300">(</span><span class="text-[#9cdcfe]">$sortColumn</span><span class="text-neutral-300">, </span><span class="text-[#9cdcfe]">$sortDirection</span><span class="text-neutral-300">)</span>
<span class="text-neutral-300">    -></span><span class="text-[#dcdcaa]">paginate</span><span class="text-neutral-300">(</span><span class="text-[#b5cea8]">25</span><span class="text-neutral-300">)</span>
<span class="text-neutral-300">    -></span><span class="text-[#dcdcaa]">withQueryString</span><span class="text-neutral-300">();</span>
<span class="text-neutral-300">        </span><span class="text-[#9cdcfe]">$pivotRecords</span><span class="text-neutral-300"> = </span><span class="text-[#9cdcfe]">$application</span><span class="text-neutral-300">-></span><span class="text-[#dcdcaa]">regionalPostgres</span><span class="text-neutral-300">()</span>
<span class="text-neutral-300">            -></span><span class="text-[#dcdcaa]">from</span><span class="text-neutral-300">(</span><span class="text-[#ce9178]">'environment_issue'</span><span class="text-neutral-300">)</span>
<span class="text-neutral-300">            -></span><span class="text-[#dcdcaa]">select</span><span class="text-neutral-300">(</span><span class="text-[#ce9178]">'issue_id'</span><span class="text-neutral-300">, </span><span class="text-[#ce9178]">'environment_id'</span><span class="text-neutral-300">)</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Expanded Vendor Frames Section -->
                            <div class="bg-white/[0.05] rounded-lg w-full">
                                <div class="flex flex-col overflow-hidden w-full">
                                    <!-- Vendor Frames Toggle -->
                                    <div class="bg-white/[0.01] flex items-center justify-between overflow-hidden pl-4 pr-2.5 py-0 h-11 w-full">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                            </svg>
                                            <span class="text-[13px] font-mono text-white tracking-[-0.26px]">3 vendor frames</span>
                                        </div>
                                        <div class="bg-white/[0.05] rounded-md w-6 h-6 flex items-center justify-center">
                                            <svg class="w-2 h-[11px] text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Individual Stack Frames -->
                                    <div class="flex items-center justify-between h-11 px-4 py-0 w-full border-t border-white/[0]">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <span class="text-[13px] font-mono text-white tracking-[-0.26px] overflow-hidden text-ellipsis">
                                                Illuminate\Pipeline\Pipeline::Illuminate\Pipeline\{closure}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs font-mono text-neutral-400 tracking-[-0.24px]">
                                                Illuminate/Pipeline/Pipeline.php<span class="text-neutral-500">:163</span>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between h-11 px-4 py-0 w-full border-t border-white/[0]">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <span class="text-[13px] font-mono text-white tracking-[-0.26px] overflow-hidden text-ellipsis">
                                                Illuminate\Routing\Middleware\SubstituteBindings::handle
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs font-mono text-neutral-400 tracking-[-0.24px]">
                                                Illuminate/Routing/Middleware/SubstituteBindings.php<span class="text-neutral-500">:82</span>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between h-11 px-4 py-0 w-full border-t border-white/[0]">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <span class="text-[13px] font-mono text-white tracking-[-0.26px] overflow-hidden text-ellipsis">
                                                Illuminate\Pipeline\Pipeline::Illuminate\Pipeline\{closure}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs font-mono text-neutral-400 tracking-[-0.24px]">
                                                vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php<span class="text-neutral-500">:65</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Stack Frame -->
                            <div class="bg-white/[0.03] rounded-lg h-11 flex items-center justify-between pl-4 pr-2.5 py-0 w-full">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-[13px] font-mono text-white tracking-[-0.26px] overflow-hidden text-ellipsis">
                                        App\Models\Flight<span class="text-white">-></span><span class="text-[#9cdcfe]">store()</span>
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-mono text-neutral-400 tracking-[-0.24px]">Services/FlightService.php:45</span>
                                    <div class="bg-white/[0.03] rounded-md w-6 h-6 flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Another Stack Frame -->
                            <div class="bg-white/[0.03] rounded-lg h-11 flex items-center justify-between pl-4 pr-2.5 py-0 w-full">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-[13px] font-mono text-white tracking-[-0.26px] overflow-hidden text-ellipsis">
                                        App\Models\Flight<span class="text-white">-></span><span class="text-[#9cdcfe]">store()</span>
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-mono text-neutral-400 tracking-[-0.24px]">Services/FlightService.php:45</span>
                                    <div class="bg-white/[0.03] rounded-md w-6 h-6 flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Vendor Frames Toggle (Another One) -->
                            <div class="bg-white/[0.01] opacity-90 rounded-lg h-11 w-full">
                                <div class="flex items-center justify-between overflow-hidden pl-4 pr-2.5 py-0 h-full w-full">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <svg class="w-3 h-3 text-neutral-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                        </svg>
                                        <span class="text-[13px] font-mono text-neutral-400 tracking-[-0.26px]">53 vendor frames</span>
                                    </div>
                                    <div class="bg-white/[0.03] rounded-md w-6 h-6 flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Final Stack Frame -->
                            <div class="bg-white/[0.03] rounded-lg h-11 flex items-center justify-between pl-4 pr-2.5 py-0 w-full">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-[13px] font-mono text-white tracking-[-0.26px] overflow-hidden text-ellipsis">
                                        App\Http\Controllers\ApiController<span class="text-white">-></span><span class="text-[#9cdcfe]">handle()</span>
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-mono text-neutral-400 tracking-[-0.24px]">app/Http/Controllers/ApiController.php:32</span>
                                    <div class="bg-white/[0.03] rounded-md w-6 h-6 flex items-center justify-center">
                                        <svg class="w-3 h-3 text-neutral-400" fill="currentColor" viewBox="0 0 20 20">
                                            <circle cx="10" cy="10" r="3" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Queries -->
            <div class="flex flex-col gap-4 w-full px-14 ">
                <div class="bg-white/[0.01] border border-neutral-800 rounded-xl w-full">
                <div class="flex flex-col overflow-hidden w-full">
                    <!-- Section Header -->
                    <div class="flex items-center justify-between pb-2 pt-[18px] px-[18px] w-full">
                        <div class="flex items-center gap-2.5 flex-1 min-w-12">
                            <div class="flex items-center gap-2.5">
                                <div class="bg-neutral-800 rounded-md w-6 h-6 flex items-center justify-center p-1">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-semibold text-white tracking-[-0.32px] leading-[24px]">Queries</h3>
                            </div>
                        </div>
                    </div>

                    <!-- Query Rows -->
                    <div class="flex flex-col gap-1 p-[10px] w-full">
                        @forelse ($exception->applicationQueries() as ['connectionName' => $connectionName, 'sql' => $sql, 'time' => $time])
                        <div class="bg-white/[0.03] rounded-md h-10 flex items-center gap-8 px-4 w-full">
                            <div class="flex items-center gap-4 flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <svg class="w-3 h-3 text-neutral-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                                    </svg>
                                    <span class="text-xs font-mono text-neutral-400 tracking-[-0.12px]">{{ $connectionName }}</span>
                                </div>
                                <div class="text-xs font-mono text-neutral-200 tracking-[-0.24px] overflow-hidden text-ellipsis flex-1 min-w-0 leading-[1.5]">
                                    <pre><code>{{ $sql }}</code></pre>
                                </div>
                            </div>
                            <div class="flex items-center gap-5">
                                <div class="flex items-center gap-2"></div>
                                <div class="flex items-center gap-2">
                                    <div class="text-xs font-mono text-neutral-200 tracking-[-0.12px] w-[65px] text-right">{{ $time }}ms</div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <x-laravel-exceptions-renderer-new::empty-state message="No queries executed" />
                        @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-laravel-exceptions-renderer-new::separator />

        <!-- Context -->
        <div class="w-full max-w-7xl mx-auto flex flex-col gap-12 p-[56px] border-x border-white/[0.09] border-dashed">
            <!-- Headers -->
            <div class="flex flex-col w-full">
                <div class="flex gap-6 items-start justify-start pb-3 pt-0 px-0 w-full">
                    <h2 class="text-lg font-semibold text-white tracking-[-0.36px] leading-[28px]">Headers</h2>
                </div>
                <div class="flex flex-col gap-5 w-full">
                    <div class="flex flex-col w-full">
                        @foreach ($exception->requestHeaders() as $key => $value)
                        <div class="flex items-center gap-2 h-10 px-0 py-0 w-full">
                            <div class="text-sm font-mono text-neutral-400 tracking-[-0.28px]">{{ $key }}</div>
                            <div class="flex-1 h-3 border-b-2 border-dotted border-white/20"></div>
                            <div class="text-sm font-mono text-white tracking-[-0.28px] max-w-[772px] overflow-hidden text-ellipsis leading-[1.6]">{{ $value }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="flex flex-col gap-2 overflow-hidden w-full">
                <div class="flex gap-6 items-start justify-start pb-3 pt-0 px-0 w-full">
                    <h2 class="text-lg font-semibold tracking-[-0.36px] leading-[28px]">Body</h2>
                </div>
                @if($body = $exception->requestBody())
                <div class="bg-white/[0.02] border border-white/5 rounded-md shadow-[0px_16px_32px_-8px_rgba(12,12,13,0.4)] w-full overflow-hidden p-5 text-sm font-mono">
                    <pre class="tracking-[-0.28px] leading-[1.7] max-w-[780px] whitespace-pre"><code>{{ $body }}</code></pre>
                </div>
                @else
                <x-laravel-exceptions-renderer-new::empty-state message="No request body" />
                @endif
            </div>

            <!-- Routing -->
            <div class="flex flex-col overflow-hidden w-full">
                <div class="flex gap-6 items-start justify-start pb-3 pt-0 px-0 w-full">
                    <h2 class="text-lg font-semibold text-white tracking-[-0.36px] leading-[28px]">Routing</h2>
                </div>
                <div class="flex flex-col w-full">
                    @foreach ($exception->applicationRouteContext() as $key => $value)
                    <div class="flex items-center gap-2 h-10 px-0 py-0 w-full">
                        <div class="text-sm font-mono text-neutral-400 tracking-[-0.28px] capitalize">{{ $key }}</div>
                        <div class="flex-1 h-3 border-b-2 border-dotted border-white/20"></div>
                        <div class="text-sm font-mono text-white tracking-[-0.28px] max-w-[772px] overflow-hidden text-ellipsis leading-[1.6]">
                            {{ $value }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Routing Parameters -->
            <div class="flex flex-col gap-2 overflow-hidden w-full">
                <div class="flex gap-6 items-start justify-start pb-3 pt-0 px-0 w-full">
                    <h2 class="text-lg font-semibold text-white tracking-[-0.36px] leading-[28px]">Routing parameters</h2>
                </div>
                @if ($routeParametersContext = $exception->applicationRouteParametersContext())
                <div class="bg-white/[0.02] border border-white/5 rounded-md shadow-[0px_16px_32px_-8px_rgba(12,12,13,0.4)] w-full overflow-hidden p-5">
                    <pre class="text-sm font-mono text-white tracking-[-0.28px] leading-[1.7] max-w-[780px] w-full"><code>{{ $routeParametersContext }}</code></pre>
                </div>
                @else
                <x-laravel-exceptions-renderer-new::empty-state message="No routing parameters" />
                @endif
            </div>
        </div>

        <x-laravel-exceptions-renderer-new::separator />

        <!-- Footer with ASCII Art -->
        <div class="w-full max-w-7xl mx-auto flex flex-col gap-12 px-14 py-0 border-x border-white/[0.09] border-dashed">
            <div class="grid grid-cols-[max-content] grid-rows-[max-content] place-items-start leading-[0] relative">
                <div class="grid-area-[1/1] h-0 mt-0 relative w-full">
                    <div class="absolute top-[-1px] left-0 right-0 bottom-0 bg-gradient-to-r from-transparent via-white/10 to-transparent h-px"></div>
                </div>
            </div>
            <div class="flex gap-2 h-[276px] items-start justify-center w-full">
                <div class="flex-1 font-mono text-xs leading-[1.09] text-transparent min-w-0 whitespace-pre" style="background: radial-gradient(25.8px 13.5px at 106px 70px, rgba(212,212,212,1) 0%, rgba(179,179,179,1) 25%, rgba(146,146,146,1) 50%, rgba(113,113,113,1) 75%, rgba(81,81,81,1) 100%); -webkit-background-clip: text; background-clip: text;">1111111111                                                                                                                                                                                                                    111111111
1011011011                                                                                                                                                                                                                    110110110
1111110111                                                                                                                                                                                                                    111101111
1101011101                                                                                                                                                                                                                    101111011
1111111111                                                                                                                                                                                                                    111011110
1011010111                                                                                                                                                                                                                    101110111
1111111101                                                                                                                                                                                                                    111111101
1010110111                                                                                                                                                                                                                    110101111
1111111111                                                                                                                                                                                                                    111111011
1101101011                                111111111                                                             111111111                                                                         111111111                   101101111
1111111110                            1111101101101111   1111111111      1111111111111111111111111          1111101101101111    111111111    111111111                    11111111111         11111011011011111               111111101
1010101111                         111101011111111101111 1101110110      1101101101011011010110111       1111010111111111011111 110110110    1101101101                  11011011011       11110111111111110110111            101010111
1111111011                       1110111111101010101110111111110111      1111011111111110111111101     1110111111101010111101101111101111     1111101111                 1111111011      111011110101010101111111111          111111110
1101101110                     111011101101111111111111101010111101      1011110110101011110101111   111011101101111111101111111010111011      101111101                1101010111      110111011111111111110110101111        110111011
1111111111                    11011111111110       1010111111101111      1110111111111111011111011  11011111111110       1101011111111110      1110111111              11111111111    11111111110           11111110111       111101111
1010110101                   11111101010              1110110111011      1011101011                11111101010              1110110101111       1111010111             1010110110    1101010101               1011011011      101111101
1111111111                   1011011111                111111111111      1111111110               11011011111                111111111011        1011111011           1111111111    1111111111                 1111111111     111011011
1101010111                  1111011101                  10110101101      1101011011               1111011101                  10101101110        1111011111          11011010101    1010110111                  101101011     101111111
1111111101                 11011110111                   1111111111      1111111111               101111011                    1111111111         1011101101         1111111111    11111111101111111111111111111111111110     111101101
1011011111                 1111011111                    1011010101      1011010110              1111011111                    1101101011          1111111111       1101011011     101101011110110110110101101010101011111    110111111
1111110101                 1011110110                    1111111111      1111111111              1101111011                     111111110           101010110      11111111111     111111110111111101111111111111111110111    111101011
1010111111                 1110111111                    1101011011      1010110101              1111101111                    1101011011           1111111111    11010110101      110110111101010111011010110110110111101    101111110
1111101101                  1111011011                   1111111111      1111111111               101111011                    1111111111            1101101101   1111111111       1111111011                                 111011011
1101111111                  1011111111                  11011010101      1101101101               1110111101                  11011010110            11111111111 1101101011         1010111111                                101111111
1111010111                   11010101111               110111111111      1111111111               111110111111               110111111111             101010110111110111110         11111010111                 1111          111101101
1011111101                   1111111101111           11011101101110      1010101011                1011110110111          111011110110101              1111111110101111011           11011111011              111011111       110111111
111011011111111111111111111   11011011101111111 1111111111111111011      1111111111                 1101111111011111  1111111111011111111              111010111111110111             1111011111111        11110111011111     111110101
101111111011010110110101101     11111111010110111011010110110101111      1101101101                   11010111110110111011010111110101101               11111101011011111               1111011010111111111101111111101       101011111
111101101111111111101111111      1101011111111101111111111101111101      1111111111                    1111101011111111111111101011111111                101111111111101                 1011111111011011011110101011         111111011
110111111010110101111101011         1111111101101010101  1111101011      1010110101                      111111110110101010111  111101011                11101011011011                    110101111111101101111111           110111110
111110101111111111011111111            11111110111111    1011111011      1111111111                          10111111111111     110111111                 1111111110111                        1101101011111101               111101011
                </div>
            </div>
        </div>
    </div>
</body>
</html>
