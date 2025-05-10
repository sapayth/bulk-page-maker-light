/**
 * Main App Component
 */
import {useState, useEffect} from '@wordpress/element';
import {__} from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

const allowedPostTypes = ['post', 'page'];
const allowedPostStatuses = ['publish', 'pending', 'draft', 'private'];
const allowedCommentStatuses = ['closed', 'open'];

const App = () => {
    const [generationMode, setGenerationMode] = useState('auto');
    const [post, setPost] = useState( {
        post_number: 1,
        post_type: 'post',
        post_status: 'publish',
        comment_status: 'closed',
        post_parent: '0',
        post_title: '',
        post_content: '',
    } );
    const [pages, setPages] = useState([]);
    const [errors, setErrors] = useState({});
    const [notice, setNotice] = useState(null);

    useEffect(() => {
        fetch(bpmData.restUrl + 'wp/v2/pages')
            .then(response => response.json())
            .then(data => setPages(data));
    }, []);

    const validate = () => {
        const newErrors = {};

        // Post status
        if (!['publish', 'pending', 'draft'].includes(post['post_status'])) {
            newErrors['post_status'] = __('Invalid status selected', 'bpm-light');
        }
        // Comment status
        if (!allowedCommentStatuses.includes(post['comment_status'])) {
            newErrors['comment_status'] = __('Invalid comment status selected', 'bpm-light');
        }
        // Parent page (if present)
        if (post['post_type'] === 'page' && post['post_parent'] && post['post_parent'] !== '0') {
            const validParent = pages.some(page => String(page.id) === String(post['post_parent']));
            if (!validParent) {
                newErrors['post_parent'] = __('Invalid parent page selected', 'bpm-light');
            }
        }

        if (generationMode === 'auto') {
            // Number of Pages/Posts
            const num = Number(post['post_number']);
            if (!num || num < 1) {
                newErrors['post_number'] = __('Number of Pages/Posts must be at least 1', 'bpm-light');
            }


        } else if (generationMode === 'manual') {
            // Post type
            if (!allowedPostTypes.includes(post['post_type'])) {
                newErrors['post_type'] = __('Invalid type selected', 'bpm-light');
            }
            // Post title
            if (!post['post_title'] || !post['post_title'].trim()) {
                newErrors['post_title'] = __('Please enter at least one title', 'bpm-light');
            }
        }
        return newErrors;
    };

    const handleGenerate = async (e) => {
        e.preventDefault();
        const validationErrors = validate();
        setErrors(validationErrors);
        if (Object.keys(validationErrors).length > 0) {
            return;
        }
        const postTitles = generationMode === 'manual' ? post['post_title'].split(',').map(title => title.trim()) : [];
        const postContents = generationMode === 'manual' ? post['post_content'].split(',').map(content => content.trim()) : [];
        const postData = {
            post_number: generationMode === 'auto' ? post['post_number'] : postTitles.length,
            post_type: post['post_type'],
            post_status: post['post_status'],
            comment_status: post['comment_status'],
            post_parent: post['post_parent'],
            post_titles: generationMode === 'manual' ? postTitles : [],
            post_contents: generationMode === 'manual' ? postContents : [],
        };

        const url = bpmData.restUrl + 'bpm/v1/posts/bulk';
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': bpmData.nonce,
            },
            body: JSON.stringify(postData),
        });

        if (!response.ok) {
            const errorData = await response.json();
            const errorMessage = errorData.message || __('An error occurred while generating posts', 'bpm-light');
            setNotice({ message: errorMessage, status: 'error' });
            return;
        }


        const data = await response.json();
        // Show success notice with number of created posts
        setNotice({
            message: __('Posts generated successfully: ', 'bpm-light') + (data.created ? data.created.length : ''),
            status: 'success',
        });
        // Redirect after short delay
        setTimeout(() => {
            window.location.href = `?page=bulk-page-maker`;
        }, 2000);
    };

    // Helper to get error class
    const errorClass = (field) => errors[field] ? 'bpm-border-red-500 bpm-outline-red-500' : '';

    return (
        <div className="bpm-p-4 bpm-form-wrapper">
            {/* Header */}
            <div className="bpm-flex bpm-items-center bpm-justify-between bpm-bg-white bpm-py-4 bpm-px-6 bpm-shadow-sm bpm-rounded-md bpm-mb-6">
                <div className="bpm-flex bpm-items-center">
                    <span className="bpm-text-xl bpm-font-semibold bpm-text-gray-900 bpm-mr-2">
                        {bpmData.pluginName || 'Bulk Page Maker Light'}
                    </span>
                    <span className="bpm-bg-green-100 bpm-text-green-700 bpm-text-xs bpm-font-semibold bpm-px-2 bpm-py-1 bpm-rounded-full">
                        v{bpmData.pluginVersion || '1.0.0'}
                    </span>
                </div>
                <a
                    href="https://wordpress.org/plugins/bulk-page-maker-light/#reviews"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="bpm-bg-green-600 hover:bpm-bg-green-700 bpm-text-white bpm-font-semibold bpm-px-5 bpm-py-2 bpm-rounded bpm-flex bpm-items-center bpm-transition-colors"
                >
                    <span className="bpm-mr-2">⭐</span> {__('Vote us on WordPress', 'bpm-light')}
                </a>
            </div>
            {notice && (
                <Notice
                    status={notice.status}
                    onRemove={() => setNotice(null)}
                    isDismissible={true}
                >
                    {notice.message}
                </Notice>
            )}
            <form onSubmit={handleGenerate}>
            <div className="bpm-flex bpm-gap-4 bpm-w-2/3">
                <div className="bpm-w-1/2">
                    <label
                        className={`bpm-transition-all bpm-relative bpm-flex bpm-cursor-pointer bpm-rounded-lg bpm-border bpm-p-4 bpm-shadow-sm focus:bpm-outline-none bpm-bg-white ${generationMode === 'auto' ? 'bpm-border-primary bpm-border-2' : ''}`}>
                        <input
                            type="radio"
                            value="auto"
                            className="bpm-sr-only"
                            checked={generationMode === 'auto'}
                            onChange={() => setGenerationMode('auto')}
                        />
                        <div className="bpm-flex-1">
                            <p className="bpm-block bpm-text-sm bpm-font-medium bpm-text-gray-900">{__( 'Auto Generate', 'bpm-light' )}</p>
                            <p className="bpm-text-sm bpm-text-gray-500">Auto generate post/page name and contents</p>
                        </div>
                        {generationMode === 'auto' && (
                            <svg className="bpm-h-5 bpm-w-5 bpm-text-primary" viewBox="0 0 20 20" fill="currentColor"
                                aria-hidden="true"
                                data-slot="icon">
                                <path fillRule="evenodd"
                                    d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                                    clipRule="evenodd"/>
                            </svg>
                        )}
                    </label>
                </div>
                <div className="bpm-w-1/2">
                    <label
                        className={`bpm-transition-all bpm-relative bpm-flex bpm-cursor-pointer bpm-rounded-lg bpm-border bpm-p-4 bpm-shadow-sm focus:bpm-outline-none bpm-bg-white ${generationMode === 'manual' ? 'bpm-border-primary bpm-border-2' : ''}`}>
                        <input
                            type="radio"
                            value="manual"
                            className="bpm-sr-only"
                            checked={generationMode === 'manual'}
                            onChange={() => setGenerationMode('manual')}
                        />
                        <div className="bpm-flex-1">
                            <p className="bpm-block bpm-text-sm bpm-font-medium bpm-text-gray-900">Manual</p>
                            <p className="bpm-text-sm bpm-text-gray-500">Manually input post/page name and contents</p>
                        </div>
                        {generationMode === 'manual' && (
                            <svg className="bpm-h-5 bpm-w-5 bpm-text-primary" viewBox="0 0 20 20" fill="currentColor"
                                aria-hidden="true"
                                data-slot="icon">
                                <path fillRule="evenodd"
                                    d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                                    clipRule="evenodd"/>
                            </svg>
                        )}
                    </label>
                </div>
            </div>
            {generationMode === 'auto' && (
                <div className="bpm-mt-4">
                    <div className="bpm-w-2/3">
                        <div className="bpm-mt-4">
                            <label htmlFor="post_number"
                                   className="bpm-block bpm-text-gray-900">
                                    {__('Number of Pages/Posts', 'bpm-light')}
                                   </label>
                            <div className="bpm-mt-2">
                                <input type="number" id="post_number" name="post_number"
                                       className={`bpm-input ${errorClass('post_number')}`}
                                       onChange={e => setPost({ ...post, 'post_number': e.target.value })}
                                       value={post['post_number'] || ''} />
                                {errors['post_number'] && <p className="bpm-text-red-500 bpm-text-sm">{errors['post_number']}</p>}
                            </div>
                        </div>
                        <div className="bpm-mt-4">
                            <label htmlFor="post_type"
                                   className="bpm-block bpm-text-gray-900">
                                    {__('Type', 'bpm-light')}
                                   </label>
                            <div className="bpm-mt-2">
                                <select id="post_type" name="post_type"
                                        className={`bpm-input ${errorClass('post_type')}`}
                                        onChange={e => setPost({ ...post, 'post_type': e.target.value })}
                                        value={post['post_type'] || 'post'}>
                                    <option value="post">Post</option>
                                    <option value="page">Page</option>
                                </select>
                                {errors['post_type'] && <p className="bpm-text-red-500 bpm-text-sm">{errors['post_type']}</p>}
                            </div>
                        </div>
                        <div className="bpm-mt-4">
                            <label htmlFor="post-status"
                                   className="bpm-block bpm-text-gray-900">Pages/Posts Status</label>
                            <div className="bpm-mt-2">
                                <select
                                    id="post-status"
                                    name="post-status"
                                    onChange={e => setPost({ ...post, 'post_status': e.target.value })}
                                    value={post['post_status'] || 'publish'}
                                    className={`bpm-input ${errorClass('post_status')}`}
                                >
                                    <option value="publish">Publish</option>
                                    <option value="pending">Pending</option>
                                    <option value="draft">Draft</option>
                                </select>
                                {errors['post_status'] && <p className="bpm-text-red-500 bpm-text-sm">{errors['post_status']}</p>}
                            </div>
                        </div>
                        <div className="bpm-mt-4">
                            <label htmlFor="comment-status"
                                   className="bpm-block bpm-text-gray-900">Comment Status</label>
                            <div className="bpm-mt-2">
                                <select
                                    id="comment-status"
                                    name="comment-status"
                                    onChange={e => setPost({ ...post, 'comment_status': e.target.value })}
                                    value={post['comment_status'] || 'publish'}
                                    className={`bpm-input ${errorClass('comment_status')}`}
                                >
                                    <option value="closed">Closed</option>
                                    <option value="open">Open</option>
                                </select>
                                {errors['comment_status'] && <p className="bpm-text-red-500 bpm-text-sm">{errors['comment_status']}</p>}
                            </div>
                        </div>
                        {post.post_type === 'page' && (
                            <div className="bpm-mt-4">
                                <label htmlFor="post_parent"
                                       className="bpm-block bpm-text-gray-900">Parent Page</label>
                                <div className="bpm-mt-2">
                                    <select id="post_parent" name="post_parent"
                                            className={`bpm-input ${errorClass('post_parent')}`}
                                            onChange={e => setPost({ ...post, 'post_parent': e.target.value })}
                                            value={post['post_parent'] || '0'}>
                                            <option value="0">{__('No Parent', 'bpm-light')}</option>
                                        {pages.map(page => (
                                            <option key={page.id} value={page.id}>{page.title.rendered}</option>
                                        ))}
                                    </select>
                                    {errors['post_parent'] && <p className="bpm-text-red-500 bpm-text-sm">{errors['post_parent']}</p>}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {generationMode === 'manual' && (
                <div className="bpm-mt-4">
                    <div className="bpm-w-2/3">
                        <div className="bpm-mt-4">
                            <label htmlFor="post_type"
                                   className="bpm-block bpm-text-gray-900">
                                    {__('Type', 'bpm-light')}
                                   </label>
                            <div className="bpm-mt-2">
                                <select id="post_type" name="post_type"
                                        className={`bpm-input ${errorClass('post_type')}`}
                                        onChange={e => setPost({ ...post, 'post_type': e.target.value })}
                                        value={post['post_type'] || 'post'}>
                                    <option value="post">Post</option>
                                    <option value="page">Page</option>
                                </select>
                                {errors['post_type'] && <p className="bpm-text-red-500 bpm-text-sm">{errors['post_type']}</p>}
                            </div>
                        </div>
                        <div className="bpm-mt-4">
                            <label
                                htmlFor="post_title"
                                className="bpm-block bpm-text-gray-900">{__('List of Pages/Posts Title (Comma Separated)', 'bpm-light')}</label>
                            <input type="text" id="post_title" name="post_title"
                                className={`bpm-input ${errorClass('post_title')}`}
                                onChange={e => setPost({ ...post, 'post_title': e.target.value })}
                                value={post['post_title'] || ''} />
                                {errors['post_title'] && <p className="bpm-text-red-500 bpm-text-sm">{errors['post_title']}</p>}
                                <p className="bpm-text-sm bpm-text-gray-500">{__('eg. Page1, Page2, page3, PAGE4, PAge5', 'bpm-light')}</p>
                        </div>
                        <div className="bpm-mt-4">
                            <label htmlFor="post_title"
                                   className="bpm-block bpm-text-gray-900">
                                {__('Page/Post content', 'bpm-light')}
                            </label>
                            <textarea id="post_content" name="post_content"
                                className={`bpm-input ${errorClass('post_content')}`}
                                onChange={e => setPost({ ...post, 'post_content': e.target.value })}
                                value={post['post_content'] || ''} />
                            {errors['post_content'] && <p className="bpm-text-red-500 bpm-text-sm">{errors['post_content']}</p>}
                            <p className="bpm-text-sm bpm-text-gray-500">{__('eg. This is the content of the page/post', 'bpm-light')}</p>
                        </div>
                        <div className="bpm-mt-4">
                            <label htmlFor="post_status"
                                   className="bpm-block bpm-text-gray-900">
                                {__('Pages/Posts Status', 'bpm-light')}
                            </label>
                            <select
                                id="post_status"
                                name="post_status"
                                onChange={e => setPost({ ...post, 'post_status': e.target.value })}
                                value={post['post_status'] || 'publish'}
                                className={`bpm-input ${errorClass('post_status')}`}
                            >
                                <option value="publish">Publish</option>
                                <option value="pending">Pending</option>
                                <option value="draft">Draft</option>
                                <option value="private">Private</option>
                            </select>
                            {errors['post_status'] && <p className="bpm-text-red-500 bpm-text-sm">{errors['post_status']}</p>}
                        </div>
                        <div className="bpm-mt-4">
                            <label htmlFor="comment_status"
                                   className="bpm-block bpm-text-gray-900">
                                {__('Comment Status', 'bpm-light')}
                            </label>
                            <select
                                id="comment_status"
                                name="comment_status"
                                onChange={e => setPost({ ...post, 'comment_status': e.target.value })}
                                value={post['comment_status'] || 'publish'}
                                className={`bpm-input ${errorClass('comment_status')}`}
                            >
                                <option value="closed">Closed</option>
                                <option value="open">Open</option>
                            </select>
                            {errors['comment_status'] && <p className="bpm-text-red-500 bpm-text-sm">{errors['comment_status']}</p>}
                        </div>
                        {post.post_type === 'page' && (
                            <div className="bpm-mt-4">
                                <label htmlFor="post_parent"
                                    className="bpm-block bpm-text-gray-900">
                                {__('Parent Page', 'bpm-light')}
                            </label>
                            <select
                                id="post_parent"
                                name="post_parent"
                                className={`bpm-input ${errorClass('post_parent')}`}
                                onChange={e => setPost({ ...post, 'post_parent': e.target.value })}
                                value={post['post_parent'] || '0'}>
                                <option value="0">{__('No Parent', 'bpm-light')}</option>
                                {pages.map(page => (
                                    <option key={page.id} value={page.id}>{page.title.rendered}</option>
                                ))}
                                </select>
                                {errors['post_parent'] && <p className="bpm-text-red-500 bpm-text-sm">{errors['post_parent']}</p>}
                            </div>
                        )}
                    </div>
                </div>
            )}

            <div className="bpm-mt-4">
                <button
                    type="submit"
                    className="bpm-bg-primary bpm-text-white bpm-px-4 bpm-py-2 bpm-rounded-md bpm-transition-colors bpm-duration-150 bpm-ease-in-out hover:bpm-bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    {__('Generate', 'bpm-light')}
                </button>
            </div>
            </form>
        </div>
    );
};

export default App;